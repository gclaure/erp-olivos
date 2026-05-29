<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\BranchService;
use App\Services\WarehouseService;
use Illuminate\Support\ServiceProvider;

class BranchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('branch', function ($app) {
            return new BranchService($app->make(WarehouseService::class));
        });

        $this->app->singleton(WarehouseService::class, function () {
            return new WarehouseService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
