<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BiInventoryDaily;
use App\Models\BiPurchasesDaily;
use App\Models\BiSalesDaily;
use App\Models\Kardex;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BIService
{
    /**
     * Update daily sales metrics for a given sale.
     */
    public function updateDailySale($sale): void
    {
        $saleId = is_object($sale) ? $sale->id : $sale;
        $saleObj = Sale::with('details')->find($saleId);
        
        if (!$saleObj) return;

        foreach ($saleObj->details as $detail) {
            $revenue = BigDecimal::of($detail->subtotal ?? '0'); // Ya incluye descuento de línea
            $cost = BigDecimal::of($detail->unit_cost ?? '0')->multipliedBy(BigDecimal::of($detail->quantity ?? '0'));
            $profit = BigDecimal::of($detail->unit_profit ?? '0')->multipliedBy(BigDecimal::of($detail->quantity ?? '0'));

            $key = [
                'date' => Carbon::parse((string)$saleObj->date)->toDateString(),
                'branch_id' => $saleObj->branch_id,
                'warehouse_id' => $saleObj->warehouse_id,
                'product_id' => $detail->product_id,
            ];

            $record = DB::table('bi_sales_daily')->where($key)->first();

            if ($record) {
                DB::table('bi_sales_daily')->where($key)->update([
                    'total_quantity' => (string)BigDecimal::of($record->total_quantity)->plus($detail->quantity),
                    'total_revenue' => (string)BigDecimal::of($record->total_revenue)->plus($revenue),
                    'total_cost' => (string)BigDecimal::of($record->total_cost)->plus($cost),
                    'total_profit' => (string)BigDecimal::of($record->total_profit)->plus($profit),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('bi_sales_daily')->insert(array_merge($key, [
                    'total_quantity' => (string)$detail->quantity,
                    'total_revenue' => (string)$revenue->toScale(2, RoundingMode::HALF_UP),
                    'total_cost' => (string)$cost->toScale(2, RoundingMode::HALF_UP),
                    'total_profit' => (string)$profit->toScale(2, RoundingMode::HALF_UP),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            $this->updateProductStats($detail->product_id);
        }
    }

    /**
     * Revert daily sales metrics for a cancelled sale.
     */
    public function revertDailySale($sale): void
    {
        $saleId = is_object($sale) ? $sale->id : $sale;
        $saleObj = Sale::with('details')->find($saleId);
        
        if (!$saleObj) return;

        foreach ($saleObj->details as $detail) {
            $revenue = BigDecimal::of($detail->subtotal ?? '0');
            $cost = BigDecimal::of($detail->unit_cost ?? '0')->multipliedBy(BigDecimal::of($detail->quantity ?? '0'));
            $profit = BigDecimal::of($detail->unit_profit ?? '0')->multipliedBy(BigDecimal::of($detail->quantity ?? '0'));

            $key = [
                'date' => Carbon::parse((string)$saleObj->date)->toDateString(),
                'branch_id' => $saleObj->branch_id,
                'warehouse_id' => $saleObj->warehouse_id,
                'product_id' => $detail->product_id,
            ];

            $record = DB::table('bi_sales_daily')->where($key)->first();

            if ($record) {
                // Ensure we don't go below zero for quantities and revenue due to rounding or anomalies
                $newQty = BigDecimal::of($record->total_quantity)->minus($detail->quantity);
                $newRev = BigDecimal::of($record->total_revenue)->minus($revenue);
                $newCost = BigDecimal::of($record->total_cost)->minus($cost);
                $newProfit = BigDecimal::of($record->total_profit)->minus($profit);

                DB::table('bi_sales_daily')->where($key)->update([
                    'total_quantity' => (string)($newQty->isNegative() ? '0' : $newQty),
                    'total_revenue' => (string)($newRev->isNegative() ? '0' : $newRev),
                    'total_cost' => (string)($newCost->isNegative() ? '0' : $newCost),
                    'total_profit' => (string)$newProfit, // Profit can legally cross 0 if we had a loss, though here we just subtract
                    'updated_at' => now(),
                ]);
            }

            $this->updateProductStats($detail->product_id);
        }
    }

    /**
     * Update daily purchase metrics.
     */
    public function updateDailyPurchase($purchase): void
    {
        $purchaseId = is_object($purchase) ? $purchase->id : $purchase;
        $purchaseObj = Purchase::with('details')->find($purchaseId);

        if (!$purchaseObj) return;

        foreach ($purchaseObj->details as $detail) {
            $cost = BigDecimal::of($detail->unit_cost ?? '0')->multipliedBy(BigDecimal::of($detail->quantity ?? '0'));

            $key = [
                'date' => Carbon::parse((string)$purchaseObj->date)->toDateString(),
                'provider_id' => $purchaseObj->provider_id,
                'warehouse_id' => $purchaseObj->warehouse_id,
                'product_id' => $detail->product_id,
            ];

            $record = DB::table('bi_purchases_daily')->where($key)->first();

            if ($record) {
                DB::table('bi_purchases_daily')->where($key)->update([
                    'total_quantity' => (string)BigDecimal::of($record->total_quantity)->plus($detail->quantity),
                    'total_cost' => (string)BigDecimal::of($record->total_cost)->plus($cost),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('bi_purchases_daily')->insert(array_merge($key, [
                    'total_quantity' => (string)$detail->quantity,
                    'total_cost' => (string)$cost->toScale(2, RoundingMode::HALF_UP),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Take a snapshot of the current inventory state.
     */
    public function takeInventorySnapshot(?string $date = null): void
    {
        $date = $date ?? now()->toDateString();
        
        $stocks = Stock::with(['product', 'warehouse'])->get();

        foreach ($stocks as $stock) {
            $lastKardex = Kardex::withoutGlobalScopes()
                ->where('product_id', $stock->product_id)
                ->where('warehouse_id', $stock->warehouse_id)
                ->latest('id')
                ->first();

            $avgCost = $lastKardex ? $lastKardex->avg_cost : '0.0000';
            $qty = BigDecimal::of($stock->quantity);
            $totalValue = $qty->multipliedBy(BigDecimal::of($avgCost));

            DB::table('bi_inventory_daily')->updateOrInsert(
                [
                    'date' => $date,
                    'warehouse_id' => $stock->warehouse_id,
                    'product_id' => $stock->product_id,
                ],
                [
                    'quantity' => $stock->quantity,
                    'avg_cost' => $avgCost,
                    'total_value' => (string) $totalValue->toScale(2, RoundingMode::HALF_UP),
                    'updated_at' => now(),
                ]
            );

            $this->updateProductStats($stock->product_id);
        }
    }

    /**
     * Update aggregated product metrics (Total Sales, Rotation, etc.)
     */
    public function updateProductStats(string $productId): void
    {
        $salesData = DB::table('bi_sales_daily')
            ->where('product_id', $productId)
            ->select([
                DB::raw('SUM(total_revenue) as revenue'),
                DB::raw('SUM(total_profit) as profit'),
                DB::raw('SUM(total_quantity) as qty')
            ])->first();

        $lastKardex = Kardex::where('product_id', $productId)->latest('id')->first();
        $lastSale = Sale::whereHas('details', fn($q) => $q->where('product_id', $productId))
            ->where('is_active', true)
            ->latest('date')
            ->first();

        $soldLast30 = DB::table('bi_sales_daily')
            ->where('product_id', $productId)
            ->where('date', '>=', now()->subDays(30))
            ->sum('total_quantity');
        
        $currentStock = DB::table('stocks')->where('product_id', $productId)->sum('quantity');
        
        $rotationIndex = '0.00';
        if ((float)$currentStock > 0) {
            $rotationIndex = (string)BigDecimal::of($soldLast30)->dividedBy(BigDecimal::of($currentStock), 2, RoundingMode::HALF_UP);
        } elseif ((float)$soldLast30 > 0) {
            $rotationIndex = '99.99';
        }

        DB::table('bi_product_stats')->updateOrInsert(
            ['product_id' => $productId],
            [
                'total_revenue' => $salesData->revenue ?? '0.00',
                'total_profit' => $salesData->profit ?? '0.00',
                'total_quantity_sold' => $salesData->qty ?? '0.0000',
                'last_cost' => $lastKardex->unit_cost ?? '0.0000',
                'avg_cost' => $lastKardex->avg_cost ?? '0.0000',
                'last_sale_at' => $lastSale->date ?? null,
                'rotation_index' => $rotationIndex,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Update daily movement metrics (In/Out) for a given kardex entry.
     */
    public function updateDailyMovement($kardex): void
    {
        $kardexId = is_object($kardex) ? $kardex->id : $kardex;
        $kardexObj = Kardex::with('warehouse')->find($kardexId);
        
        if (!$kardexObj) return;

        $branchId = $kardexObj->branch_id ?? ($kardexObj->warehouse->branch_id ?? null);
        
        if (!$branchId) {
            // Log or skip if no branch found (unlikely in valid data)
            return;
        }

        $isInput = in_array($kardexObj->type, ['compra', 'ajuste_entrada', 'transferencia_entrada']);
        $quantity = BigDecimal::of($kardexObj->quantity);
        $costValue = $quantity->multipliedBy(BigDecimal::of($kardexObj->unit_cost));

        $key = [
            'date' => Carbon::parse((string)$kardexObj->date)->toDateString(),
            'branch_id' => $branchId,
            'warehouse_id' => $kardexObj->warehouse_id,
            'product_id' => $kardexObj->product_id,
        ];

        $data = [
            'total_input_quantity' => $isInput ? (string)$quantity : '0',
            'total_output_quantity' => !$isInput ? (string)$quantity : '0',
            'input_cost_value' => $isInput ? (string)$costValue->toScale(2, RoundingMode::HALF_UP) : '0',
            'output_cost_value' => !$isInput ? (string)$costValue->toScale(2, RoundingMode::HALF_UP) : '0',
        ];

        $record = DB::table('bi_movements_daily')->where($key)->first();

        if ($record) {
            DB::table('bi_movements_daily')->where($key)->update([
                'total_input_quantity' => (string)BigDecimal::of($record->total_input_quantity)->plus($data['total_input_quantity']),
                'total_output_quantity' => (string)BigDecimal::of($record->total_output_quantity)->plus($data['total_output_quantity']),
                'input_cost_value' => (string)BigDecimal::of($record->input_cost_value)->plus($data['input_cost_value']),
                'output_cost_value' => (string)BigDecimal::of($record->output_cost_value)->plus($data['output_cost_value']),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('bi_movements_daily')->insert(array_merge($key, $data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Recalculate ABC ranking for all products based on revenue.
     */
    public function recalculateABCRankings(): void
    {
        $stats = \App\Models\BiProductStats::orderByDesc('total_revenue')->get();
        $totalRevenue = $stats->sum(fn($s) => (float)$s->total_revenue);

        if ($totalRevenue <= 0) return;

        $cumulativeRevenue = 0;
        foreach ($stats as $stat) {
            $cumulativeRevenue += (float)$stat->total_revenue;
            $percent = ($cumulativeRevenue / $totalRevenue) * 100;

            if ($percent <= 80) {
                $rank = 'A';
            } elseif ($percent <= 95) {
                $rank = 'B';
            } else {
                $rank = 'C';
            }

            DB::table('bi_product_stats')->where('id', $stat->id)->update(['abc_rank' => $rank]);
        }
    }
}
