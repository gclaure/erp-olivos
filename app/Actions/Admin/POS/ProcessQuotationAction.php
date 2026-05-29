<?php

declare(strict_types=1);

namespace App\Actions\Admin\POS;

use App\Models\Quotation;
use App\Services\QuotationService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProcessQuotationAction
{
    private QuotationService $quotationService;

    public function __construct(QuotationService $quotationService)
    {
        $this->quotationService = $quotationService;
    }

    /**
     * @param array<string, mixed> $data
     * @return Quotation
     */
    public function execute(array $data): Quotation
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
                    'quantity'   => (string) $itemQty->toScale(4, RoundingMode::HALF_UP),
                    'unit_price' => (string) $itemPrice->toScale(4, RoundingMode::HALF_UP),
                    'discount'   => (string) $itemDiscountAmount->toScale(4, RoundingMode::HALF_UP),
                    'subtotal'   => (string) $itemSubtotal->toScale(2, RoundingMode::HALF_UP),
                ];
            }

            $globalDiscountAmount = BigDecimal::zero();
            if ($isFixedDiscount) {
                $globalDiscountAmount = $globalDiscountInput;
            } else {
                if ($globalDiscountInput->isGreaterThan(100)) $globalDiscountInput = BigDecimal::of(100);
                $globalDiscountAmount = $subtotal->multipliedBy($globalDiscountInput)->dividedBy(100, 4, RoundingMode::HALF_UP);
            }

            $totalPayment = $subtotal->minus($globalDiscountAmount)->plus($deliveryCost);
            if ($totalPayment->isNegative()) $totalPayment = BigDecimal::zero();

            $quotationData = [
                'client_id'       => $data['client_id'],
                'user_id'         => Auth::id(),
                'warehouse_id'    => $data['warehouse_id'],
                'date'            => now()->format('Y-m-d'),
                'valid_until'     => now()->addDays(15)->format('Y-m-d'),
                'total'           => (string) $totalPayment->toScale(2, RoundingMode::HALF_UP),
                'subtotal'        => (string) $subtotal->toScale(2, RoundingMode::HALF_UP),
                'global_discount' => (string) $globalDiscountAmount->toScale(4, RoundingMode::HALF_UP),
                'total_payment'   => (string) $totalPayment->toScale(2, RoundingMode::HALF_UP),
                'notes'           => 'Cotización desde POS',
                'status'          => 'pendiente',
                'delivery_mode'           => $data['delivery_mode'],
                'delivery_address'        => $data['delivery_address'] ?? null,
                'delivery_contact_name'   => $data['delivery_contact_name'] ?? null,
                'delivery_contact_phone'  => $data['delivery_contact_phone'] ?? null,
                'delivery_at'             => $data['delivery_at'] ?? null,
                'delivery_observations'   => $data['delivery_observations'] ?? null,
                'delivery_cost'           => $data['delivery_cost'] ?? 0,
                'shipping_company'        => $data['shipping_company'] ?? null,
                'shipping_origin'         => $data['shipping_origin'] ?? null,
                'shipping_destination'    => $data['shipping_destination'] ?? null,
            ];

            return $this->quotationService->create($quotationData, $details);
        });
    }
}
