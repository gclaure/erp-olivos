<?php

declare(strict_types=1);

namespace App\Actions\Admin\POS;

use App\Enums\DeliveryMode;
use App\Models\Sale;
use App\Services\SaleService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ProcessSaleAction
{
    private SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * @param array<string, mixed> $data
     * @return Sale
     * @throws Exception
     */
    public function execute(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $cart = $data['cart'];
            $isFixedDiscount = $data['is_fixed_discount'];
            $globalDiscountInput = BigDecimal::of($data['global_discount'] ?? 0);
            $deliveryCost = BigDecimal::of($data['delivery_cost'] ?? 0);

            $subtotal = BigDecimal::zero();
            $details = [];

            foreach ($cart as $item) {
                $itemQty = BigDecimal::of($item['quantity']);
                $itemPrice = BigDecimal::of($item['price']);
                $itemLineTotal = $itemQty->multipliedBy($itemPrice);
                
                $itemDiscountAmount = BigDecimal::zero();
                $itemDiscountInput = BigDecimal::of($item['discount'] ?? 0);

                if ($isFixedDiscount) {
                    $itemDiscountAmount = $itemDiscountInput;
                } else {
                    if ($itemDiscountInput->isGreaterThan(100)) $itemDiscountInput = BigDecimal::of(100);
                    $itemDiscountAmount = $itemLineTotal->multipliedBy($itemDiscountInput)->dividedBy(100, 4, RoundingMode::HALF_UP);
                }

                $itemSubtotal = $itemLineTotal->minus($itemDiscountAmount);
                if ($itemSubtotal->isNegative()) $itemSubtotal = BigDecimal::zero();

                $subtotal = $subtotal->plus($itemSubtotal);

                $details[] = [
                    'product_id' => $item['id'],
                    'quantity' => (string) $itemQty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) $itemPrice->toScale(4, RoundingMode::HALF_UP),
                    'discount' => (string) $itemDiscountAmount->toScale(4, RoundingMode::HALF_UP),
                    'subtotal' => (string) $itemSubtotal->toScale(2, RoundingMode::HALF_UP),
                    'glosa' => $item['glosa'] ?? null,
                ];
            }

            // Cálculo de Descuento Global
            $globalDiscountAmount = BigDecimal::zero();
            if ($isFixedDiscount) {
                $globalDiscountAmount = $globalDiscountInput;
            } else {
                if ($globalDiscountInput->isGreaterThan(100)) $globalDiscountInput = BigDecimal::of(100);
                $globalDiscountAmount = $subtotal->multipliedBy($globalDiscountInput)->dividedBy(100, 4, RoundingMode::HALF_UP);
            }

            if ($globalDiscountAmount->isGreaterThanOrEqualTo($subtotal) && !$subtotal->isZero()) {
                throw new Exception("El descuento global no puede cubrir el total de la venta.");
            }

            $totalPayment = $subtotal->minus($globalDiscountAmount)->plus($deliveryCost);
            if ($totalPayment->isNegative()) $totalPayment = BigDecimal::zero();

            // Determinar Status y Balance
            $status = 'completada';
            $balance = '0.00';

            if ($data['payment_type'] === 'credito') {
                $downPaymentAmount = BigDecimal::of($data['down_payment'] ?? 0);
                if ($downPaymentAmount->isGreaterThan($totalPayment)) {
                    throw new Exception("El adelanto no puede ser mayor al total de la venta.");
                }

                if ($downPaymentAmount->isZero()) {
                    $status = 'pendiente';
                } elseif ($downPaymentAmount->isLessThan($totalPayment)) {
                    $status = 'parcial';
                } else {
                    $status = 'pagado';
                }
                $balance = (string) $totalPayment->minus($downPaymentAmount)->toScale(2, RoundingMode::HALF_UP);
            }

            $saleData = [
                'client_id' => $data['client_id'],
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => Auth::id(),
                'date' => now()->format('Y-m-d'),
                'total' => (string) $totalPayment->toScale(2, RoundingMode::HALF_UP),
                'subtotal' => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                'global_discount' => (string) $globalDiscountAmount->toScale(2, RoundingMode::HALF_UP),
                'total_payment' => (string) $totalPayment->toScale(2, RoundingMode::HALF_UP),
                'status' => $status,
                'payment_type' => $data['payment_type'],
                'credit_days' => $data['payment_type'] === 'credito' ? (int) $data['credit_days'] : null,
                'due_date' => $data['payment_type'] === 'credito' ? $data['due_date'] : null,
                'down_payment' => $data['payment_type'] === 'credito' ? (string) ((float) $data['down_payment'] ?? 0) : '0',
                'balance' => $balance,
                'delivery_mode' => $data['delivery_mode'],
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_contact_name' => $data['delivery_contact_name'] ?? null,
                'delivery_contact_phone' => $data['delivery_contact_phone'] ?? null,
                'delivery_at' => $data['delivery_at'] ?? null,
                'delivery_observations' => $data['delivery_observations'] ?? null,
                'delivery_cost' => $data['delivery_cost'] ?? 0,
                'shipping_company' => $data['shipping_company'] ?? null,
                'shipping_origin' => $data['shipping_origin'] ?? null,
                'shipping_destination' => $data['shipping_destination'] ?? null,
            ];

            return $this->saleService->create($saleData, $details);
        });
    }
}
