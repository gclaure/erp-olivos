<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private PurchaseService $purchaseService
    ) {}

    /**
     * @param array<string, mixed> $purchaseOrderData
     * @param array<int, array<string, mixed>> $details
     * @throws Exception
     */
    public function create(array $purchaseOrderData, array $details): PurchaseOrder
    {
        return DB::transaction(function () use ($purchaseOrderData, $details): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::create([
                'provider_id' => $purchaseOrderData['provider_id'],
                'warehouse_id' => $purchaseOrderData['warehouse_id'],
                'user_id' => $purchaseOrderData['user_id'],
                'consumption_request_id' => $purchaseOrderData['consumption_request_id'] ?? null,
                'date' => $purchaseOrderData['date'],
                'notes' => $purchaseOrderData['notes'] ?? null,
                'status' => 'pendiente',
                'total' => $purchaseOrderData['total'],
            ]);

            foreach ($details as $detail) {
                $qty = BigDecimal::of((string) $detail['quantity']);
                $unitPrice = BigDecimal::of((string) $detail['unit_price']);
                $subtotal = BigDecimal::of((string) $detail['subtotal']);

                $purchaseOrder->details()->create([
                    'product_id' => $detail['product_id'],
                    'consumption_request_detail_id' => $detail['consumption_request_detail_id'] ?? null,
                    'quantity' => (string) $qty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) $unitPrice->toScale(4, RoundingMode::HALF_UP),
                    'subtotal' => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                ]);
            }

            return $purchaseOrder;
        });
    }

    /**
     * @throws Exception
     */
    public function changeStatus(PurchaseOrder $purchaseOrder, string $status): void
    {
        if ($purchaseOrder->status === 'completada') {
            throw new Exception('No se puede alterar el estado de una Orden convertida a Compra.');
        }

        if (!in_array($status, ['pendiente', 'aprobada', 'cancelada'], true)) {
            throw new Exception('Estado inválido para la orden.');
        }

        $purchaseOrder->update(['status' => $status]);
    }

    /**
     * Cancela una línea individual de una orden de compra abierta.
     * Si todas las líneas de la orden quedan canceladas, la orden pasa automáticamente a 'cancelada'.
     */
    public function cancelDetailLine(PurchaseOrderDetail $detail, string $reason): PurchaseOrderDetail
    {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new DomainException('Debe indicar el motivo para cancelar la línea de compra.');
        }

        $order = PurchaseOrder::withoutGlobalScopes()
            ->lockForUpdate()
            ->findOrFail($detail->purchase_order_id);

        if (!in_array($order->status, ['pendiente', 'aprobada'], true)) {
            throw new DomainException('Solo se pueden cancelar líneas de órdenes de compra pendientes o aprobadas.');
        }

        if (!$detail->isCancelled()) {
            $detail->cancelled_at = now();
            $detail->cancellation_reason = $cleanReason;
            $detail->save();
        }

        $activeDetails = PurchaseOrderDetail::query()
            ->where('purchase_order_id', $order->id)
            ->whereNull('cancelled_at')
            ->orderBy('id')
            ->get();

        if ($activeDetails->isEmpty()) {
            $order->status = 'cancelada';
            $order->total = '0.00';
            $order->save();
        } else {
            $newTotal = BigDecimal::zero();
            foreach ($activeDetails as $activeLine) {
                $newTotal = $newTotal->plus(BigDecimal::of((string) $activeLine->subtotal));
            }
            $order->total = (string) $newTotal->toScale(2, RoundingMode::HALF_UP);
            $order->save();
        }

        return $detail;
    }

    /**
     * Convierte una orden de compra aprobada en compra real, ignorando líneas canceladas.
     *
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
    ): Purchase {
        return DB::transaction(function () use ($purchaseOrderId, $userId, $voucherType, $paymentType, $dueDate, $downPayment, $receiptPath): Purchase {
            $order = PurchaseOrder::withoutGlobalScopes()
                ->with('details')
                ->lockForUpdate()
                ->findOrFail($purchaseOrderId);

            if ($order->status !== 'aprobada') {
                throw new Exception('Solo las Órdenes Aprobadas por el Administrador pueden convertirse en Compra.');
            }

            $activeDetails = $order->details->filter(
                static fn (PurchaseOrderDetail $d): bool => !$d->isCancelled()
            );

            if ($activeDetails->isEmpty()) {
                throw new DomainException('No se puede convertir en compra una orden cuyas líneas han sido todas canceladas.');
            }

            $purchaseDetails = [];
            $activeTotal = BigDecimal::zero();

            foreach ($activeDetails as $d) {
                $subtotalDecimal = BigDecimal::of((string) $d->subtotal)->toScale(2, RoundingMode::HALF_UP);
                $activeTotal = $activeTotal->plus($subtotalDecimal);

                $purchaseDetails[] = [
                    'product_id' => $d->product_id,
                    'quantity' => (string) BigDecimal::of((string) $d->quantity)->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) BigDecimal::of((string) $d->unit_price)->toScale(4, RoundingMode::HALF_UP),
                    'subtotal' => (string) $subtotalDecimal,
                ];
            }

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
                'total' => (string) $activeTotal->toScale(2, RoundingMode::HALF_UP),
                'status' => $paymentType === 'credito' ? 'pendiente' : 'completada',
            ];

            $purchase = $this->purchaseService->createPurchase($purchaseData, $purchaseDetails);

            $order->update([
                'status' => 'completada',
                'total' => (string) $activeTotal->toScale(2, RoundingMode::HALF_UP),
            ]);

            return $purchase;
        });
    }
}
