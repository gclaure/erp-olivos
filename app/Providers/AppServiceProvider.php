<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Gate;
use App\Models\Stock;
use App\Observers\StockObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('company.service', function ($app) {
            return new \App\Services\CompanyService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        Stock::observe(StockObserver::class);

        // BI Analytics Listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\SaleCreated::class,
            \App\Listeners\UpdateBIAnalytics::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PurchaseCreated::class,
            \App\Listeners\UpdateBIAnalytics::class
        );

        // Implicitly grant "Super Admin" and "Admin" roles all permissions
        Gate::before(function ($user, $ability) {
            if ($user->is_super_admin) {
                return true;
            }

            // Users with Admin role also get all permissions within their tenant
            if ($user->hasRole(['Admin', 'Administrador', 'admin', 'administrador'])) {
                return true;
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
