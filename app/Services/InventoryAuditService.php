<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kardex;
use App\Models\Stock;
use App\Models\Company;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class InventoryAuditService
{
    /**
     * Niveles de inconsistencia detectados.
     */
    public const LEVEL_WARNING = 'WARNING';
    public const LEVEL_HARD = 'HARD';
    public const LEVEL_BLOCKING = 'BLOCKING';

    /**
     * Reconcilia el Stock Agregado (Materialized View) contra el Kardex (SSoT).
     */
    public function reconcile(?string $productId = null): array
    {
        $query = Stock::query();
        
        if ($productId) {
            $query->where('product_id', $productId);
        }

        $stocks = $query->get();
        $inconsistencies = [];

        foreach ($stocks as $stock) {
            $kardexStats = DB::table('kardexes')
                ->where('product_id', $stock->product_id)
                ->where('warehouse_id', $stock->warehouse_id)
                ->whereNull('deleted_at') // Asumiendo soft deletes si existen
                ->select(
                    DB::raw('SUM(CASE 
                        WHEN movement_type IN ("PURCHASE", "ADJUSTMENT_IN", "TRANSFER_IN", "RETURN", "INITIAL_LAYER") THEN quantity 
                        ELSE -ABS(quantity) 
                    END) as total_qty'),
                    DB::raw('SUM(total_cost) as total_value')
                )
                ->first();

            $stockQty = BigDecimal::of($stock->quantity);
            $kardexQty = BigDecimal::of($kardexStats->total_qty ?? 0);

            if (!$stockQty->isEqualTo($kardexQty)) {
                $inconsistencies[] = [
                    'product_id' => $stock->product_id,
                    'warehouse_id' => $stock->warehouse_id,
                    'type' => 'QUANTITY_MISMATCH',
                    'level' => self::LEVEL_HARD,
                    'expected' => (string) $kardexQty,
                    'actual' => (string) $stockQty,
                    'diff' => (string) $kardexQty->minus($stockQty),
                ];
            }
        }

        return $inconsistencies;
    }

    /**
     * Verifica la integridad de las capas FIFO.
     */
    public function auditFifoLayers(): array
    {
        // Buscar capas con remaining_quantity > 0 que no sumen el stock total
        // O consumos huérfanos.
        return []; // Implementación futura avanzada
    }
}
