<?php

declare(strict_types=1);

namespace App\Strategies;

use App\Contracts\InventoryStrategy;
use App\Enums\KardexMovementType;
use App\Models\Kardex;
use App\Models\Stock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class WeightedAverageStrategy implements InventoryStrategy
{
    public function recordEntry(array $data, Stock $stock): Kardex
    {
        return DB::transaction(function () use ($data, $stock) {
            $stock = Stock::withoutGlobalScopes()->where('id', $stock->id)->lockForUpdate()->firstOrFail();

            $quantity = BigDecimal::of($data['quantity']);
            $unitCost = BigDecimal::of($data['unit_cost']);
            $movementTotalCost = $quantity->multipliedBy($unitCost);

            $oldQuantity = BigDecimal::of($stock->quantity);
            $oldTotalValue = BigDecimal::of($stock->inventory_value);

            $newQuantity = $oldQuantity->plus($quantity);
            $newTotalValue = $oldTotalValue->plus($movementTotalCost);

            $newAvgCost = $newQuantity->isGreaterThan(0)
                ? $newTotalValue->dividedBy($newQuantity, 8, RoundingMode::HALF_UP)
                : $unitCost;

            // Actualizar Stock (Proyección / Materialized View)
            $stock->update([
                'quantity' => (string) $newQuantity,
                'inventory_value' => (string) $newTotalValue,
                'average_cost' => (string) $newAvgCost,
                'version' => $stock->version + 1,
            ]);

            // Crear Kardex (SSoT Inmutable)
            return Kardex::create(array_merge($data, [
                'movement_type' => $data['movement_type'] ?? KardexMovementType::ENTRY,
                'balance_quantity' => (string) $newQuantity,
                'balance_total_cost' => (string) $newTotalValue, // Deprecated but kept for compatibility
                'balance_after' => (string) $newQuantity,
                'value_after' => (string) $newTotalValue,
                'avg_cost' => (string) $newAvgCost,
                'processed_at' => now(),
                'total_cost' => (string) $movementTotalCost,
            ]));
        });
    }

    public function recordExit(array $data, Stock $stock): Kardex
    {
        return DB::transaction(function () use ($data, $stock) {
            $stock = Stock::withoutGlobalScopes()->where('id', $stock->id)->lockForUpdate()->firstOrFail();

            $quantity = BigDecimal::of($data['quantity']);
            
            // En Promedio Ponderado, la salida usa el costo promedio actual del stock
            $unitCost = BigDecimal::of($stock->average_cost);
            $movementTotalCost = $quantity->multipliedBy($unitCost);

            $oldQuantity = BigDecimal::of($stock->quantity);
            $oldTotalValue = BigDecimal::of($stock->inventory_value);

            if ($oldQuantity->isLessThan($quantity)) {
                throw new \RuntimeException("Stock insuficiente para realizar la salida (Ponderado).");
            }

            $newQuantity = $oldQuantity->minus($quantity);
            $newTotalValue = $oldTotalValue->minus($movementTotalCost);

            // Si el stock llega a cero, el valor debe ser cero
            if ($newQuantity->isZero()) {
                $newTotalValue = BigDecimal::zero();
            }

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
                'unit_cost' => (string) $unitCost,
                'movement_type' => $data['movement_type'] ?? KardexMovementType::SALE,
                'balance_quantity' => (string) $newQuantity,
                'balance_total_cost' => (string) $newTotalValue,
                'balance_after' => (string) $newQuantity,
                'value_after' => (string) $newTotalValue,
                'avg_cost' => (string) $newAvgCost,
                'processed_at' => now(),
                'total_cost' => (string) $movementTotalCost,
            ]));
        });
    }

    public function reverse(Kardex $kardex, Stock $stock): void
    {
        // En Promedio Ponderado, la reversión simplemente deshace el movimiento
        // pero requiere recalcular el promedio si es una entrada reversada.
        DB::transaction(function () use ($kardex, $stock) {
            $stock = Stock::withoutGlobalScopes()->where('id', $stock->id)->lockForUpdate()->firstOrFail();

            $qty = BigDecimal::of($kardex->quantity);
            $total = BigDecimal::of($kardex->total_cost);

            $isEntry = $kardex->movement_type->isEntry(); // Assuming helper in enum or logic here

            if ($kardex->movement_type === KardexMovementType::PURCHASE || $qty->isGreaterThan(0)) {
                // Era una entrada, revertirla es una salida
                $newQty = BigDecimal::of($stock->quantity)->minus($qty);
                $newTotal = BigDecimal::of($stock->inventory_value)->minus($total);
            } else {
                // Era una salida, revertirla es una entrada
                $newQty = BigDecimal::of($stock->quantity)->plus($qty->abs());
                $newTotal = BigDecimal::of($stock->inventory_value)->plus($total->abs());
            }

            $newAvg = $newQty->isGreaterThan(0) 
                ? $newTotal->dividedBy($newQty, 8, RoundingMode::HALF_UP)
                : BigDecimal::of($stock->average_cost);

            $stock->update([
                'quantity' => (string) $newQty,
                'inventory_value' => (string) $newTotal,
                'average_cost' => (string) $newAvg,
                'version' => $stock->version + 1,
            ]);
        });
    }
}
