<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Kardex;
use App\Models\Purchase;
use App\Models\Stock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Illuminate\Support\Facades\DB;

readonly class CancelPurchaseService
{
    public function __construct(
        private KardexService $kardexService
    ) {}
    /**
     * @param Purchase $purchase
     * @param string $reason
     * @param int|string $userId ID of the user performing the cancellation
     * @return Purchase
     * @throws Exception
     */
    public function cancel(Purchase $purchase, string $reason, $userId): Purchase
    {
        if ($purchase->status === 'cancelado') {
            throw new Exception('La compra ya se encuentra cancelada.');
        }

        return DB::transaction(function () use ($purchase, $reason, $userId) {
            // 1. Update purchase status and reason
            $purchase->update([
                'status' => 'cancelado',
                'cancellation_reason' => $reason,
            ]);

            // 2. Load details to revert stock
            $purchase->load('details');

            foreach ($purchase->details as $detail) {
                // Register Reversal in Kardex
                $this->kardexService->record(
                    type: 'SALIDA',
                    productId: (string) $detail->product_id,
                    warehouseId: (string) $purchase->warehouse_id,
                    quantity: (string) BigDecimal::of($detail->quantity)->toScale(4, RoundingMode::HALF_UP),
                    unitCost: (string) BigDecimal::of($detail->unit_price)->toScale(4, RoundingMode::HALF_UP),
                    userId: $userId,
                    notes: 'Reversión por cancelación de compra. Motivo: ' . $reason,
                    recordableType: Purchase::class,
                    recordableId: $purchase->id
                );
            }

            return $purchase;
        });
    }
}
