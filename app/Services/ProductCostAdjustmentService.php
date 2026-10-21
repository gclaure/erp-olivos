<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kardex;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Stock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCostAdjustmentService
{
    /**
     * Ajusta el costo unitario de un producto manteniendo estrictamente intacto el stock físico.
     * Recalcula la valorización en la tabla stocks, sincroniza el Kardex y actualiza los registros origen.
     *
     * @param Product $product
     * @param float|string $newUnitCost
     * @param string|null $warehouseId
     * @param string|null $reason
     * @param string|int|null $userId
     * @return array
     */
    public function adjustCost(
        Product $product,
        float|string $newUnitCost,
        ?string $warehouseId = null,
        ?string $reason = null,
        string|int|null $userId = null
    ): array {
        return DB::transaction(function () use ($product, $newUnitCost, $warehouseId, $reason, $userId) {
            $costBigDecimal = BigDecimal::of((string) $newUnitCost)->toScale(4, RoundingMode::HALF_UP);

            // 1. Obtener y bloquear los stocks del producto (por almacén o todos los almacenes)
            $stockQuery = Stock::withoutGlobalScopes()
                ->where('product_id', $product->id)
                ->lockForUpdate();

            if (!empty($warehouseId)) {
                $stockQuery->where('warehouse_id', $warehouseId);
            }

            $stocks = $stockQuery->get();
            $updatedStocks = [];

            foreach ($stocks as $stock) {
                $oldCost = (string) $stock->average_cost;
                $quantity = BigDecimal::of((string) $stock->quantity);

                // El stock físico (quantity) se mantiene intacto. Solo se recalcula el valor total.
                $newInventoryValue = $quantity->multipliedBy($costBigDecimal)->toScale(2, RoundingMode::HALF_UP);

                $stock->update([
                    'average_cost' => (string) $costBigDecimal,
                    'inventory_value' => (string) $newInventoryValue,
                    'version' => $stock->version + 1,
                ]);

                // 2. Sincronizar Kardex para este producto y almacén
                $kardexEntries = Kardex::where('product_id', $product->id)
                    ->where('warehouse_id', $stock->warehouse_id)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($kardexEntries as $kardex) {
                    $kQty = BigDecimal::of((string) $kardex->quantity);
                    $newKardexTotalCost = $kQty->multipliedBy($costBigDecimal)->toScale(2, RoundingMode::HALF_UP);
                    $kBalanceQty = BigDecimal::of((string) ($kardex->balance_quantity ?? $kardex->balance_after ?? $kQty));
                    $newBalanceTotalCost = $kBalanceQty->multipliedBy($costBigDecimal)->toScale(2, RoundingMode::HALF_UP);

                    DB::table('kardexes')->where('id', $kardex->id)->update([
                        'unit_cost' => (string) $costBigDecimal,
                        'total_cost' => (string) $newKardexTotalCost,
                        'avg_cost' => (string) $costBigDecimal,
                        'balance_total_cost' => (string) $newBalanceTotalCost,
                        'value_after' => (string) $newBalanceTotalCost,
                        'updated_at' => now(),
                    ]);
                }

                $updatedStocks[] = [
                    'warehouse_id' => $stock->warehouse_id,
                    'quantity' => (string) $quantity,
                    'old_average_cost' => $oldCost,
                    'new_average_cost' => (string) $costBigDecimal,
                    'new_inventory_value' => (string) $newInventoryValue,
                ];
            }

            // 3. Sincronizar comprobantes de compra iniciales donde figure este producto
            $purchaseDetails = PurchaseDetail::where('product_id', $product->id)->get();
            $affectedPurchaseIds = [];

            foreach ($purchaseDetails as $detail) {
                $detailQty = BigDecimal::of((string) $detail->quantity);
                $newSubtotal = $detailQty->multipliedBy($costBigDecimal)->toScale(2, RoundingMode::HALF_UP);

                $detail->update([
                    'unit_price' => (string) $costBigDecimal,
                    'subtotal' => (string) $newSubtotal,
                ]);

                $affectedPurchaseIds[] = $detail->purchase_id;
            }

            // Recalcular total de las compras afectadas
            foreach (array_unique($affectedPurchaseIds) as $purchaseId) {
                $purchase = Purchase::find($purchaseId);
                if ($purchase) {
                    $sumSubtotal = PurchaseDetail::where('purchase_id', $purchaseId)->sum('subtotal');
                    $purchase->update([
                        'total' => (string) BigDecimal::of((string) $sumSubtotal)->toScale(2, RoundingMode::HALF_UP),
                    ]);
                }
            }

            Log::info('Ajuste de costo unitario realizado', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'new_cost' => (string) $costBigDecimal,
                'user_id' => $userId,
                'reason' => $reason,
                'stocks_affected' => count($updatedStocks),
            ]);

            return [
                'success' => true,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'new_cost' => (string) $costBigDecimal,
                'stocks' => $updatedStocks,
            ];
        });
    }
}
