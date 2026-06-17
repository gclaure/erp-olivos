<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'inertia';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'            => $request->user()->id,
                    'name'          => $request->user()->name,
                    'email'         => $request->user()->email,
                    'is_super_admin'=> (bool)$request->user()->is_super_admin,
                    'roles'         => $request->user()->roles->pluck('name')->toArray(),
                    'area'          => $request->user()->area,
                ] : null,
            ],
            'company_slug' => \App\Facades\CompanyFacade::getCompany()?->slug ?? \App\Models\Company::first()?->slug,
            'appName' => config('app.name'),
            'appUrl' => config('app.url'),
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'success_data' => session('success_data'),
            ],
            // Datos compartidos perezosos (solo se envían si se solicitan o en carga inicial)
            'activeBranch' => fn () => [
                'hasActive' => \App\Facades\Branch::hasActiveBranch(),
                'name' => \App\Facades\Branch::getActiveBranchName(),
                'id' => \App\Facades\Branch::getActiveBranchId(),
            ],
            'availableBranches' => fn () => ($request->user()?->is_super_admin || $request->user()?->hasRole(['Admin', 'Administrador', 'admin', 'administrador']))
                ? \App\Models\Branch::where('is_active', true)
                    ->get(['id', 'name'])
                : ($request->user() ? \App\Models\Branch::where('id', $request->user()->branch_id)->get(['id', 'name']) : []),
            'activePOS' => fn () => [
                'id' => null,
                'name' => null,
                'receipt_type' => 'media',
            ],
            'company' => fn () => [
                'name' => \App\Facades\CompanyFacade::getCompany()?->name,
                'slug' => \App\Facades\CompanyFacade::getCompany()?->slug ?? \App\Models\Company::first()?->slug,
                'logo_url' => \App\Facades\CompanyFacade::getCompany()?->logo_path 
                    ? asset('storage/' . \App\Facades\CompanyFacade::getCompany()->logo_path) 
                    : null,
                'receipt_type' => \App\Facades\CompanyFacade::getCompany()?->receipt_type ?? 'media',
                'show_name' => true,
                'plan' => \App\Facades\CompanyFacade::getCompany()?->currentPlan() ? [
                    'name' => \App\Facades\CompanyFacade::getCompany()->currentPlan()->name,
                    'slug' => \App\Facades\CompanyFacade::getCompany()->currentPlan()->slug,
                    'features' => \App\Facades\CompanyFacade::getCompany()->currentPlan()->features,
                ] : null,
            ],
            'cart' => fn () => [
                'items' => session()->get('cart', []),
                'total' => array_reduce(session()->get('cart', []), function ($carry, $item) {
                    return $carry + ($item['price'] * $item['quantity']);
                }, 0),
            ],
            'notifications' => fn () => $request->user() ? [
                'latest' => $request->user()->unreadNotifications()->latest()->limit(5)->get(),
                'unreadCount' => $request->user()->unreadNotifications()->count(),
            ] : null,
            'ecommerceSettings' => fn () => str_contains($request->url(), '/tienda') ? \Illuminate\Support\Facades\Cache::remember(
                "ecommerce_settings_default",
                3600,
                function() {
                    return \App\Models\EcommerceSetting::firstOrCreate(
                        [],
                        [
                            'store_name' => \App\Facades\CompanyFacade::getCompany()?->name ?? config('app.name'),
                            'primary_color' => '#4f46e5',
                            'secondary_color' => '#0f172a',
                            'tertiary_color' => '#1e293b',
                            'background_color' => '#0f172a',
                            'card_color' => '#1e293b',
                            'text_color' => '#f8fafc',
                        ]
                    );
                }
            ) : null,
            'ecommerceBranches' => fn () => str_contains($request->url(), '/tienda') 
                ? \App\Models\Branch::where('is_active', true)
                    ->select(['id', 'name', 'address', 'phone', 'whatsapp_number', 'latitude', 'longitude', 'is_main'])
                    ->get()
                : [],
        ];
    }
}
