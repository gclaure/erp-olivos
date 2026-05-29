<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\ExtensionType;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\SubscriptionResource;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index(Request $request): Response
    {
        $subscriptions = Subscription::with(['tenant', 'plan', 'nextPlan'])
            ->when($request->search, function ($q) use ($request) {
                $q->whereHas('tenant', fn ($t) => $t->where('name', 'ilike', '%' . $request->search . '%'));
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Subscriptions/Index', [
            'subscriptions' => SubscriptionResource::collection($subscriptions),
            'filters' => $request->only(['search', 'status']),
            'tenants' => Company::select('id', 'name')->orderBy('name')->get(),
            'plans' => Plan::where('is_active', true)->select('id', 'name', 'price', 'billing_period')->get(),
            'statusOptions' => SubscriptionStatus::cases(),
        ]);
    }

    public function assign(Request $request): RedirectResponse
    {
        $request->validate([
            'tenant_id' => 'required|exists:companies,id',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = Company::findOrFail($request->tenant_id);
        $plan = Plan::findOrFail($request->plan_id);

        $this->subscriptionService->create($tenant, $plan);

        return back()->with('success', 'Suscripción asignada correctamente.');
    }

    public function extend(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1',
            'type' => 'required|in:GIFT,PROMO,SUPPORT,COMPENSATION',
            'reason' => 'nullable|string|max:500',
        ]);

        $type = ExtensionType::from($request->type);
        $this->subscriptionService->extend($subscription, $request->days, $type, $request->reason);

        return back()->with('success', "Suscripción extendida {$request->days} días.");
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $this->subscriptionService->cancel($subscription);
        return back()->with('success', 'Suscripción cancelada.');
    }

    public function renew(Subscription $subscription): RedirectResponse
    {
        $this->subscriptionService->renew($subscription);
        return back()->with('success', 'Suscripción renovada.');
    }

    public function calculateProration(Request $request, Subscription $subscription): array
    {
        $request->validate([
            'new_plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::findOrFail($request->new_plan_id);
        $proration = $this->subscriptionService->calculateProration($subscription);
        
        return [
            'unused_value' => $proration['unused_value'],
            'new_price' => (float) $newPlan->price,
            'is_upgrade' => (float) $newPlan->price > (float) $subscription->plan->price,
            'balance_due' => max(0, (float) $newPlan->price - $proration['unused_value']),
            'remaining_days' => $proration['remaining_days'],
        ];
    }

    public function changePlan(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'new_plan_id' => 'required|exists:plans,id',
        ]);

        $newPlan = Plan::findOrFail($request->new_plan_id);
        $result = $this->subscriptionService->changePlan($subscription, $newPlan);

        if ($result['type'] === 'UPGRADE') {
            return back()->with('success', "Upgrade exitoso. Crédito aplicado: Bs. {$result['credit_applied']}. Saldo cobrado: Bs. {$result['balance_due']}");
        }

        return back()->with('success', $result['message']);
    }
}
