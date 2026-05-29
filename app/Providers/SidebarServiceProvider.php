<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\SidebarService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SidebarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SidebarService::class);
    }

    public function boot(): void
    {
        View::composer('layouts.admin', function ($view): void {
            /** @var SidebarService $sidebar */
            $sidebar = app(SidebarService::class);
            $view->with('sidebarItems', $sidebar->getItems());
        });
    }
}
