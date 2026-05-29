<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PurchaseCreated;
use App\Events\SaleCreated;
use App\Services\BIService;

class UpdateBIAnalytics
{
    public function __construct(private BIService $biService) {}

    public function handle(object $event): void
    {
        if ($event instanceof SaleCreated) {
            $this->biService->updateDailySale($event->sale);
        }

        if ($event instanceof PurchaseCreated) {
            $this->biService->updateDailyPurchase($event->purchase);
        }
    }
}
