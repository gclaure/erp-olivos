<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AccountReceivable;
use App\Models\AccountReceivablePayment;
use App\Models\Sale;
use App\Models\Client;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

readonly class AccountReceivableService
{
    /**
     * Create an account receivable for a sale.
     */
    public function createForSale(Sale $sale, ?string $downPayment = null): AccountReceivable
    {
        return DB::transaction(function () use ($sale, $downPayment) {
            $ar = AccountReceivable::create([
                'sale_id'      => $sale->id,
                'client_id'    => $sale->client_id,
                'total_amount' => $sale->total,
                'paid_amount'  => '0.0000',
                'balance'      => $sale->total,
                'due_date'     => $sale->due_date,
                'status'       => 'pendiente',
            ]);

            // Update client's current balance
            $client = $sale->client;
            $newBalance = BigDecimal::of($client->current_balance)->plus($sale->total);
            $client->update([
                'current_balance' => (string) $newBalance->toScale(4, RoundingMode::HALF_UP)
            ]);

            // Record down payment if provided
            if ($downPayment && BigDecimal::of($downPayment)->isGreaterThan(0)) {
                $this->recordPayment($ar, [
                    'amount'         => $downPayment,
                    'payment_date'   => \Carbon\Carbon::parse($sale->date)->format('Y-m-d'),
                    'payment_method' => 'EFECTIVO', // Default initial payment
                    'reference'      => 'Adelanto inicial (Venta N° ' . $sale->number . ')',
                    'notes'          => 'Pago inicial registrado al momento de la venta.',
                    'user_id'        => $sale->user_id,
                ]);
            }

            return $ar->fresh();
        });
    }

    /**
     * Record a payment for an account receivable.
     */
    public function recordPayment(AccountReceivable $ar, array $data): AccountReceivablePayment
    {
        return DB::transaction(function () use ($ar, $data) {
            $paymentAmount = BigDecimal::of($data['amount']);
            
            $payment = $ar->payments()->create([
                'amount'         => (string) $paymentAmount->toScale(4, RoundingMode::HALF_UP),
                'payment_date'   => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference'      => $data['reference'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'user_id'        => $data['user_id'],
            ]);

            $newPaidAmount = BigDecimal::of($ar->paid_amount)->plus($paymentAmount);
            $newBalance = BigDecimal::of($ar->total_amount)->minus($newPaidAmount);

            $status = 'parcial';
            if ($newBalance->isLessThanOrEqualTo(0)) {
                $status = 'pagado';
                $newBalance = BigDecimal::zero();
            }

            $ar->update([
                'paid_amount' => (string) $newPaidAmount->toScale(4, RoundingMode::HALF_UP),
                'balance'     => (string) $newBalance->toScale(4, RoundingMode::HALF_UP),
                'status'      => $status,
            ]);

            // Update client's current balance
            $client = $ar->client;
            $newClientBalance = BigDecimal::of($client->current_balance)->minus($paymentAmount);
            $client->update([
                'current_balance' => (string) $newClientBalance->toScale(4, RoundingMode::HALF_UP)
            ]);

            $saleUpdateData = [
                'balance' => (string) $newBalance->toScale(4, RoundingMode::HALF_UP)
            ];

            if ($status === 'pagado') {
                $saleUpdateData['status'] = 'pagado';
            } elseif ($ar->sale->status === 'pendiente') {
                $saleUpdateData['status'] = 'parcial';
            }

            $ar->sale->update($saleUpdateData);

            return $payment;
        });
    }

    /**
     * Check if a client has enough credit limit for a given amount.
     */
    public function hasAvailableCredit(Client $client, string $amount): bool
    {
        if (BigDecimal::of($client->credit_limit)->isZero()) {
            return true; // 0 means unlimited credit
        }

        $projectedBalance = BigDecimal::of($client->current_balance)->plus($amount);
        return $projectedBalance->isLessThanOrEqualTo($client->credit_limit);
    }
}
