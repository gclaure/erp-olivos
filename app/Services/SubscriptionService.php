<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ExtensionType;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionCycle;
use App\Models\SubscriptionExtension;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function create(Company $tenant, Plan $plan, ?int $durationDays = null): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan, $durationDays): Subscription {
            // Cancelar suscripciones activas existentes
            $tenant->subscriptions()
                ->where('status', SubscriptionStatus::ACTIVE)
                ->update(['status' => SubscriptionStatus::CANCELLED->value]);

            $startsAt = now();
            $anchorDay = $startsAt->day;
            
            // Calcular fecha de fin determinística o usar duración personalizada
            $endsAt = $durationDays 
                ? $startsAt->copy()->addDays($durationDays)
                : $this->calculateNextPeriodEnd($startsAt, $anchorDay, $plan->billing_period);

            $subscription = Subscription::create([
                'plan_id' => $plan->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'billing_anchor_day' => $anchorDay,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => false,
            ]);

            // Registrar el primer ciclo y su pago
            $cycle = $this->recordCycle($subscription, $startsAt, $endsAt);
            $this->recordPayment($cycle, (float) $plan->price);

            return $subscription;
        });
    }

    public function renew(Subscription $subscription, ?int $durationDays = null): Subscription
    {
        $plan = $subscription->next_plan_id 
            ? $subscription->nextPlan 
            : $subscription->plan;

        $startsAt = $subscription->ends_at ?? now();
        $anchorDay = $subscription->billing_anchor_day ?? $startsAt->day;

        return DB::transaction(function () use ($subscription, $plan, $startsAt, $anchorDay): Subscription {
            $endsAt = $this->calculateNextPeriodEnd($startsAt, $anchorDay, $plan->billing_period);

            $subscription->update([
                'plan_id' => $plan->id,
                'next_plan_id' => null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $cycle = $this->recordCycle($subscription, $startsAt, $endsAt);
            $this->recordPayment($cycle, (float) $plan->price);

            return $subscription;
        });
    }

    public function cancel(Subscription $subscription): void
    {
        $subscription->update(['status' => SubscriptionStatus::CANCELLED->value]);
    }

    public function extend(
        Subscription $subscription,
        int $days,
        ExtensionType $type,
        ?string $reason = null,
    ): SubscriptionExtension {
        return DB::transaction(function () use ($subscription, $days, $type, $reason): SubscriptionExtension {
            $newEndsAt = $subscription->ends_at
                ? $subscription->ends_at->addDays($days)
                : now()->addDays($days);

            $subscription->update(['ends_at' => $newEndsAt]);

            $currentCycle = $subscription->currentCycle();
            if ($currentCycle && $currentCycle->period_end->isFuture()) {
                $currentCycle->update(['period_end' => $newEndsAt]);
            }

            if ($subscription->status === SubscriptionStatus::EXPIRED) {
                $subscription->update(['status' => SubscriptionStatus::ACTIVE->value]);
            }

            return SubscriptionExtension::create([
                'subscription_id' => $subscription->id,
                'days_added' => $days,
                'type' => $type,
                'reason' => $reason,
                'added_by' => Auth::id(),
            ]);
        });
    }

    public function checkAndExpireSubscriptions(): int
    {
        return Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update(['status' => SubscriptionStatus::EXPIRED->value]);
    }

    /**
     * Cambia el plan de una suscripción siguiendo reglas profesionales.
     */
    public function changePlan(Subscription $subscription, Plan $newPlan): array
    {
        $currentPlan = $subscription->plan;

        $isUpgrade = (float) $newPlan->price > (float) $currentPlan->price;

        if ($isUpgrade) {
            return $this->handleUpgrade($subscription, $newPlan);
        }

        return $this->handleDowngrade($subscription, $newPlan);
    }

    /**
     * Mejora de plan: Inmediata con prorrateo.
     */
    private function handleUpgrade(Subscription $subscription, Plan $newPlan): array
    {
        $proration = $this->calculateProration($subscription);
        $unusedValue = $proration['unused_value'];
        
        $newPrice = (float) $newPlan->price;
        $balanceToPay = max(0, $newPrice - $unusedValue);
        
        return DB::transaction(function () use ($subscription, $newPlan, $unusedValue, $balanceToPay) {
            $startsAt = now();
            $anchorDay = $startsAt->day;
            $endsAt = $this->calculateNextPeriodEnd($startsAt, $anchorDay, $newPlan->billing_period);

            $subscription->update([
                'plan_id' => $newPlan->id,
                'next_plan_id' => null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'billing_anchor_day' => $anchorDay,
                'status' => SubscriptionStatus::ACTIVE,
            ]);

            $cycle = $this->recordCycle($subscription, $startsAt, $endsAt);
            
            // El pago de un upgrade registra el saldo neto cobrado
            if ($balanceToPay > 0) {
                $this->recordPayment($cycle, $balanceToPay, "Diferencia Upgrade (Crédito aplicado: Bs. {$unusedValue})");
            }

            return [
                'type' => 'UPGRADE',
                'credit_applied' => $unusedValue,
                'balance_due' => $balanceToPay,
                'new_period_end' => $endsAt,
            ];
        });
    }

    /**
     * Bajada de plan: Programada para el próximo ciclo.
     */
    private function handleDowngrade(Subscription $subscription, Plan $newPlan): array
    {
        $subscription->update(['next_plan_id' => $newPlan->id]);

        return [
            'type' => 'DOWNGRADE',
            'scheduled_for' => $subscription->ends_at,
            'message' => "El cambio al plan {$newPlan->name} se aplicará automáticamente el " . $subscription->ends_at->format('d/m/Y'),
        ];
    }

    /**
     * Calcula el valor prorrateado (no consumido) del ciclo actual.
     */
    public function calculateProration(Subscription $subscription): array
    {
        $plan = $subscription->plan;
        $currentCycle = $subscription->currentCycle();

        if (!$currentCycle) {
            return ['unused_value' => 0, 'total_days' => 0, 'remaining_days' => 0];
        }

        $start = $currentCycle->period_start->startOfDay();
        $end = $currentCycle->period_end->startOfDay();
        $now = now()->startOfDay();

        if ($now->isAfter($end)) {
            return ['unused_value' => 0, 'total_days' => 0, 'remaining_days' => 0];
        }

        $totalDays = max(1, (int) $start->diffInDays($end));
        $remainingDays = max(0, (int) $now->diffInDays($end));
        
        $price = (float) $plan->price;
        $unusedValue = ($remainingDays / $totalDays) * $price;

        return [
            'unused_value' => round($unusedValue, 2),
            'total_days' => $totalDays,
            'remaining_days' => $remainingDays,
        ];
    }

    /**
     * Calcula la fecha de fin del siguiente periodo respetando el día ancla.
     */
    public function calculateNextPeriodEnd(\Carbon\CarbonInterface $currentDate, int $anchorDay, string $period): \Carbon\CarbonInterface
    {
        $next = $currentDate;

        if ($period === Plan::PERIOD_YEARLY) {
            $next = $next->addYear();
        } else {
            $next = $next->addMonth();
        }

        // Ajustar al día ancla (End-of-month alignment)
        $daysInMonth = $next->daysInMonth;
        if ($anchorDay > $daysInMonth) {
            $next = $next->day($daysInMonth);
        } else {
            $next = $next->day($anchorDay);
        }

        return $next->startOfDay();
    }

    /**
     * Registra un ciclo de suscripciones como un evento inmutable.
     */
    private function recordCycle(Subscription $subscription, \Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): SubscriptionCycle
    {
        return SubscriptionCycle::create([
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'period_start' => $start,
            'period_end' => $end,
            'status' => 'PENDING',
        ]);
    }

    /**
     * Registra un pago y lo asocia al ciclo y tenant.
     */
    private function recordPayment(SubscriptionCycle $cycle, float $amount, ?string $notes = null): void
    {
        $payment = \App\Models\Payment::create([
            'subscription_cycle_id' => $cycle->id,
            'amount' => (string) $amount,
            'currency' => 'BOB',
            'paid_at' => now(),
            'method' => \App\Enums\PaymentMethod::TRANSFER, // Default
            'status' => \App\Enums\PaymentStatus::PAID,
            'notes' => $notes,
        ]);

        $cycle->update([
            'payment_id' => $payment->id,
            'status' => 'PAID',
        ]);

        // Asegurar que la empresa esté activa al recibir el pago
        $cycle->subscription->tenant->update([
            'status' => \App\Enums\TenantStatus::ACTIVE,
        ]);
    }
}
