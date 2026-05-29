<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountPayable;
use App\Models\AccountPayablePayment;
use App\Models\Purchase;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

readonly class AccountPayableService
{
    public function createForPurchase(Purchase $purchase): AccountPayable
    {
        return AccountPayable::create([
            'purchase_id'  => $purchase->id,
            'provider_id'  => $purchase->provider_id,
            'total_amount' => $purchase->total,
            'paid_amount'  => '0.0000',
            'balance'      => $purchase->total,
            'due_date'     => $purchase->due_date,
            'status'       => 'PENDING',
        ]);
    }

    public function recordPayment(AccountPayable $accountPayable, array $data): AccountPayablePayment
    {
        return DB::transaction(function () use ($accountPayable, $data) {
            $paymentAmount = BigDecimal::of($data['amount']);
            
            $payment = $accountPayable->payments()->create([
                'amount'         => (string) $paymentAmount->toScale(4, RoundingMode::HALF_UP),
                'payment_date'   => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'receipt_path'   => $data['receipt_path'] ?? null,
                'user_id'        => $data['user_id'],
            ]);

            $newPaidAmount = BigDecimal::of($accountPayable->paid_amount)->plus($paymentAmount);
            $newBalance = BigDecimal::of($accountPayable->total_amount)->minus($newPaidAmount);

            $status = 'PARTIAL';
            if ($newBalance->isLessThanOrEqualTo(0)) {
                $status = 'PAID';
                $newBalance = BigDecimal::zero();
            }

            $accountPayable->update([
                'paid_amount' => (string) $newPaidAmount->toScale(4, RoundingMode::HALF_UP),
                'balance'     => (string) $newBalance->toScale(4, RoundingMode::HALF_UP),
                'status'      => $status,
            ]);

            // Update Purchase status if fully paid
            if ($status === 'PAID') {
                $accountPayable->purchase->update(['status' => 'PAGADO']);
            }

            return $payment;
        });
    }
}
