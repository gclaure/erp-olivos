<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Exception;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private PurchaseService $purchaseService
    ) {}

    /**
     * @param array $purchaseOrderData
     * @param array $details
     * @return PurchaseOrder
     * @throws Exception
     */
    public function create(array $purchaseOrderData, array $details): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrderData, $details) {
            $purchaseOrder = PurchaseOrder::create([
                'provider_id' => $purchaseOrderData['provider_id'],
                'warehouse_id' => $purchaseOrderData['warehouse_id'],
                'user_id' => $purchaseOrderData['user_id'],
                'date' => $purchaseOrderData['date'],
                'notes' => $purchaseOrderData['notes'] ?? null,
                'status' => 'pendiente',
                'total' => $purchaseOrderData['total'],
            ]);

            foreach ($details as $detail) {
                $qty = BigDecimal::of($detail['quantity']);
                $unitPrice = BigDecimal::of($detail['unit_price']);
                $subtotal = BigDecimal::of($detail['subtotal']);

                $purchaseOrder->details()->create([
                    'product_id' => $detail['product_id'],
                    'quantity' => (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                    'subtotal' => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                ]);
            }

            return $purchaseOrder;
        });
    }

    /**
     * @param PurchaseOrder $purchaseOrder
     * @param string $status Enums ('pendiente', 'aprobada', 'cancelada')
     * @return void
     * @throws Exception
     */
    public function changeStatus(PurchaseOrder $purchaseOrder, string $status): void
    {
        if ($purchaseOrder->status === 'completada') {
            throw new Exception("No se puede alterar el estado de una Orden convertida a Compra.");
        }

        if (!in_array($status, ['pendiente', 'aprobada', 'cancelada'])) {
            throw new Exception("Estado inválido para la orden.");
        }

        $purchaseOrder->update(['status' => $status]);
    }

    /**
     * @param string $purchaseOrderId
     * @param string $userId
     * @param string $voucherType
     * @param string $paymentType
     * @param string|null $dueDate
     * @param string $downPayment
     * @param string|null $receiptPath
     * @return \App\Models\Purchase
     * @throws Exception
     */
    public function convertToPurchase(
        string $purchaseOrderId,
        string $userId,
        string $voucherType,
        string $paymentType,
        ?string $dueDate = null,
        string $downPayment = '0',
        ?string $receiptPath = null
    ): \App\Models\Purchase {
        return DB::transaction(function () use ($purchaseOrderId, $userId, $voucherType, $paymentType, $dueDate, $downPayment, $receiptPath) {
            $order = PurchaseOrder::with('details')->findOrFail($purchaseOrderId);

            if ($order->status !== 'aprobada') {
                throw new Exception("Solo las Órdenes Aprobadas por el Administrador pueden convertirse en Compra.");
            }

            // Preparar datos para el PurchaseService real
            $purchaseData = [
                'provider_id' => $order->provider_id,
                'warehouse_id' => $order->warehouse_id,
                'user_id' => $userId,
                'date' => now()->format('Y-m-d'),
                'due_date' => $paymentType === 'credito' ? $dueDate : null,
                'voucher_type' => $voucherType,
                'payment_type' => $paymentType,
                'down_payment' => $downPayment,
                'receipt_path' => $receiptPath,
                'notes' => "Conversión desde Orden de Compra #{$order->id}. " . $order->notes,
                'total' => $order->total,
                'status' => $paymentType === 'credito' ? 'pendiente' : 'completada',
            ];

            $purchaseDetails = [];
            foreach ($order->details as $d) {
                $purchaseDetails[] = [
                    'product_id' => $d->product_id,
                    'quantity' => (string) BigDecimal::of($d->quantity)->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) BigDecimal::of($d->unit_price)->toScale(4, RoundingMode::HALF_UP),
                    'subtotal' => (string) BigDecimal::of($d->subtotal)->toScale(2, RoundingMode::HALF_UP),
                ];
            }

            // Invocar el PurchaseService (esto ya afecta Stock y Kardex)
            $purchase = $this->purchaseService->createPurchase($purchaseData, $purchaseDetails);

            // Cambiar Estado
            $order->update(['status' => 'completada']);

            return $purchase;
        });
    }
}
