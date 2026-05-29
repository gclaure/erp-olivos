<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Asesor;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminDashboardController extends Controller
{
    public function index(): Response
    {
        $totalTenants = Company::count();
        $activeTenants = Company::where('status', TenantStatus::ACTIVE)->count();
        $trialTenants = Company::where('status', TenantStatus::TRIAL)->count();
        $suspendedTenants = Company::where('status', TenantStatus::SUSPENDED)->count();

        $newTenantsThisMonth = Company::where('created_at', '>=', now()->startOfMonth())->count();

        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE)->count();
        $expiredSubscriptions = Subscription::where('status', SubscriptionStatus::EXPIRED)->count();

        $expiringSoon = Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->count();

        $totalRevenue = Payment::where('status', PaymentStatus::PAID)->sum('amount');

        $monthlyRevenue = Payment::where('status', PaymentStatus::PAID)
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $pendingPayments = Payment::where('status', PaymentStatus::PENDING)->sum('amount');

        $recentTenants = Company::latest()
            ->take(5)
            ->get()
            ->map(fn ($tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status->value,
                'status_label' => $tenant->status->label(),
                'created_at_human' => $tenant->created_at?->diffForHumans(),
            ]);

        $expiringSoonList = Subscription::with(['tenant', 'plan'])
            ->where('status', SubscriptionStatus::ACTIVE)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->addDays(15)])
            ->orderBy('ends_at')
            ->take(5)
            ->get()
            ->map(fn ($sub) => [
                'id' => $sub->id,
                'tenant' => ['name' => $sub->tenant?->name],
                'plan' => ['name' => $sub->plan?->name],
                'ends_at' => $sub->ends_at?->toIso8601String(),
                'days_remaining' => $sub->daysRemaining(),
            ]);

        $asesorStats = Asesor::withCount('companies')
            ->orderBy('companies_count', 'desc')
            ->get();

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => [
                'totalTenants' => $totalTenants,
                'activeTenants' => $activeTenants,
                'trialTenants' => $trialTenants,
                'suspendedTenants' => $suspendedTenants,
                'newTenantsThisMonth' => $newTenantsThisMonth,
                'activeSubscriptions' => $activeSubscriptions,
                'expiredSubscriptions' => $expiredSubscriptions,
                'expiringSoon' => $expiringSoon,
                'totalRevenue' => $totalRevenue,
                'monthlyRevenue' => $monthlyRevenue,
                'pendingPayments' => $pendingPayments,
            ],
            'recentTenants' => $recentTenants,
            'expiringSoonList' => $expiringSoonList,
            'asesorStats' => $asesorStats,
        ]);
    }
}
