<?php

declare(strict_types=1);

namespace App\Strategies;

use App\Contracts\InventoryStrategy;
use App\Enums\KardexMovementType;
use App\Models\Kardex;
use App\Models\KardexFifoConsumption;
use App\Models\Stock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FifoStrategy implements InventoryStrategy
{
    private const MAX_RETRIES = 3;

    public function recordEntry(array $data, Stock $stock): Kardex
    {
        return $this->withRetry(function () use ($data, $stock) {
            return DB::transaction(function () use ($data, $stock) {
                $stock = Stock::withoutGlobalScopes()->where('id', $stock->id)->lockForUpdate()->firstOrFail();

                $quantity = BigDecimal::of($data['quantity']);
                $unitCost = BigDecimal::of($data['unit_cost']);
                $movementTotalCost = $quantity->multipliedBy($unitCost);

                $oldQuantity = BigDecimal::of($stock->quantity);
                $oldTotalValue = BigDecimal::of($stock->inventory_value);

                $newQuantity = $oldQuantity->plus($quantity);
                $newTotalValue = $oldTotalValue->plus($movementTotalCost);
                
                // El costo promedio sigue calculándose para reportes rápidos
                $newAvgCost = $newQuantity->isGreaterThan(0)
                    ? $newTotalValue->dividedBy($newQuantity, 8, RoundingMode::HALF_UP)
                    : $unitCost;

                $stock->update([
                    'quantity' => (string) $newQuantity,
                    'inventory_value' => (string) $newTotalValue,
                    'average_cost' => (string) $newAvgCost,
                    'version' => $stock->version + 1,
                ]);

                return Kardex::create(array_merge($data, [
                    'movement_type' => $data['movement_type'] ?? KardexMovementType::PURCHASE,
                    'balance_quantity' => (string) $newQuantity,
                    'balance_total_cost' => (string) $newTotalValue,
                    'balance_after' => (string) $newQuantity,
                    'value_after' => (string) $newTotalValue,
                    'remaining_quantity' => (string) $quantity,
                    'is_fifo_layer' => true,
                    'processed_at' => now(),
                    'total_cost' => (string) $movementTotalCost,
                ]));
            });
        });
    }

    public function recordExit(array $data, Stock $stock): Kardex
    {
        return $this->withRetry(function () use ($data, $stock) {
            return DB::transaction(function () use ($data, $stock) {
                $stock = Stock::withoutGlobalScopes()->where('id', $stock->id)->lockForUpdate()->firstOrFail();

                $quantityToExit = BigDecimal::of($data['quantity']);
                $originalExitQty = $quantityToExit;

                if (BigDecimal::of($stock->quantity)->isLessThan($quantityToExit)) {
                    throw new \RuntimeException("Stock insuficiente para salida FIFO.");
                }

                // 1. Crear el registro de salida (SSoT) primero para tener el ID
                $kardexExit = Kardex::create(array_merge($data, [
                    'movement_type' => $data['movement_type'] ?? KardexMovementType::SALE,
                    'balance_quantity' => '0',
                    'balance_total_cost' => '0',
                    'processed_at' => now(),
                    'total_cost' => '0', // Se actualizará al final
                ]));

                $totalExitCost = BigDecimal::zero();
                
                // 2. Buscar capas disponibles (PostgreSQL optimizado con el índice parcial)
                $layers = Kardex::withoutGlobalScopes()
                    ->where('product_id', $stock->product_id)
                    ->where('warehouse_id', $stock->warehouse_id)
                    ->where('remaining_quantity', '>', 0)
                    ->orderBy('movement_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($layers as $layer) {
                    if ($quantityToExit->isZero()) break;

                    $layerQty = BigDecimal::of($layer->remaining_quantity);
                    $consumeQty = $quantityToExit->isLessThan($layerQty) ? $quantityToExit : $layerQty;
                    
                    $layerUnitCost = BigDecimal::of($layer->unit_cost);
                    $consumptionCost = $consumeQty->multipliedBy($layerUnitCost);
                    
                    $totalExitCost = $totalExitCost->plus($consumptionCost);
                    $quantityToExit = $quantityToExit->minus($consumeQty);

                    // Actualizar capa (SSoT)
                    // NOTA: Rompemos la inmutabilidad técnica del campo 'remaining_quantity' por diseño de capas
                    // pero el registro en sí no cambia su esencia (costo, fecha, etc.)
                    $layer->remaining_quantity = (string) $layerQty->minus($consumeQty);
                    $layer->saveQuietly();

                    // Registrar consumo
                    KardexFifoConsumption::create([
                        'product_id' => $stock->product_id,
                        'warehouse_id' => $stock->warehouse_id,
                        'input_kardex_id' => $layer->id,
                        'output_kardex_id' => $kardexExit->id,
                        'quantity' => (string) $consumeQty,
                        'unit_cost' => (string) $layerUnitCost,
                        'total_cost' => (string) $consumptionCost,
                        'status' => 'ACTIVE',
                    ]);
                }

                if ($quantityToExit->isGreaterThan(0)) {
                    throw new \RuntimeException("Error crítico: No se pudieron cubrir las unidades con las capas disponibles.");
                }

                // 3. Actualizar snapshots y totales del registro de salida
                $newQuantity = BigDecimal::of($stock->quantity)->minus($originalExitQty);
                $newTotalValue = BigDecimal::of($stock->inventory_value)->minus($totalExitCost);
                
                if ($newQuantity->isZero()) {
                    $newTotalValue = BigDecimal::zero();
                }

                $newAvgCost = $newQuantity->isGreaterThan(0)
                    ? $newTotalValue->dividedBy($newQuantity, 8, RoundingMode::HALF_UP)
                    : BigDecimal::of($stock->average_cost);

                $kardexExit->updateQuietly([
                    'total_cost' => (string) $totalExitCost,
                    'unit_cost' => (string) $totalExitCost->dividedBy($originalExitQty, 8, RoundingMode::HALF_UP),
                    'balance_quantity' => (string) $newQuantity,
                    'balance_total_cost' => (string) $newTotalValue,
                    'balance_after' => (string) $newQuantity,
                    'value_after' => (string) $newTotalValue,
                    'avg_cost' => (string) $newAvgCost,
                ]);

                // 4. Actualizar Stock (Materialized View)
                $stock->update([
                    'quantity' => (string) $newQuantity,
                    'inventory_value' => (string) $newTotalValue,
                    'average_cost' => (string) $newAvgCost,
                    'version' => $stock->version + 1,
                ]);

                return $kardexExit;
            });
        });
    }

    public function reverse(Kardex $kardex, Stock $stock): void
    {
        $this->withRetry(function () use ($kardex, $stock) {
            DB::transaction(function () use ($kardex, $stock) {
                $stock = Stock::withoutGlobalScopes()->where('id', $stock->id)->lockForUpdate()->firstOrFail();

                if ($kardex->is_fifo_layer) {
                    // Revertir una entrada: Invalidar la capa
                    // Solo si no ha sido consumida todavía (o manejar el error)
                    if (BigDecimal::of($kardex->remaining_quantity)->isLessThan(BigDecimal::of($kardex->quantity))) {
                        throw new \RuntimeException("No se puede revertir una capa que ya tiene consumos FIFO.");
                    }
                    
                    $qty = BigDecimal::of($kardex->quantity);
                    $total = BigDecimal::of($kardex->total_cost);

                    $newQuantity = BigDecimal::of($stock->quantity)->minus($qty);
                    $newTotalValue = BigDecimal::of($stock->inventory_value)->minus($total);
                } else {
                    // Revertir una salida: Restaurar las capas consumidas
                    $consumptions = $kardex->fifoConsumptions()->where('status', 'ACTIVE')->get();
                    $totalRestoredCost = BigDecimal::zero();

                    foreach ($consumptions as $consumption) {
                        $inputLayer = $consumption->inputKardex()->lockForUpdate()->first();
                        $restoredQty = BigDecimal::of($consumption->quantity);
                        
                        $inputLayer->remaining_quantity = (string) BigDecimal::of($inputLayer->remaining_quantity)->plus($restoredQty);
                        $inputLayer->saveQuietly();

                        $consumption->update(['status' => 'REVERSED']);
                        $totalRestoredCost = $totalRestoredCost->plus(BigDecimal::of($consumption->total_cost));
                    }

                    $newQuantity = BigDecimal::of($stock->quantity)->plus(BigDecimal::of($kardex->quantity)->abs());
                    $newTotalValue = BigDecimal::of($stock->inventory_value)->plus($totalRestoredCost);
                }

                $newAvgCost = $newQuantity->isGreaterThan(0)
                    ? $newTotalValue->dividedBy($newQuantity, 8, RoundingMode::HALF_UP)
                    : BigDecimal::of($stock->average_cost);

                $stock->update([
                    'quantity' => (string) $newQuantity,
                    'inventory_value' => (string) $newTotalValue,
                    'average_cost' => (string) $newAvgCost,
                    'version' => $stock->version + 1,
                ]);
            });
        });
    }

    /**
     * Helper para reintentos en caso de Deadlock (PostgreSQL 40P01)
     */
    private function withRetry(callable $callback)
    {
        $retries = 0;
        while (true) {
            try {
                return $callback();
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->getCode() === '40P01' && $retries < self::MAX_RETRIES) {
                    $retries++;
                    usleep(100000 * $retries); // Backoff exponencial corto
                    continue;
                }
                throw $e;
            }
        }
    }
}
