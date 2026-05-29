<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Models\Sale;
use App\Services\BIService;
use Brick\Math\BigDecimal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateBIAnalytics extends Command
{
    protected $signature = 'bi:populate';
    protected $description = 'Populate BI analytical tables from existing transactional data';

    public function handle(BIService $biService): int
    {
        $this->info('Cleaning existing BI data aggressively...');
        DB::statement('TRUNCATE TABLE bi_sales_daily RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE bi_purchases_daily RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE bi_inventory_daily RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE bi_product_stats RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE bi_movements_daily RESTART IDENTITY CASCADE');

        $this->info('Populating Sales data...');
        Sale::where('is_active', true)->each(function ($sale) use ($biService) {
            try {
                $biService->updateDailySale($sale);
            } catch (\Exception $e) {
                $this->error("SALE ERROR [{$sale->id}]: " . $e->getMessage());
            }
        });

        $this->info('Populating Purchase data...');
        Purchase::each(function ($purchase) use ($biService) {
            try {
                $biService->updateDailyPurchase($purchase);
            } catch (\Exception $e) {
                $this->error("PURCHASE ERROR [{$purchase->id}]: " . $e->getMessage());
            }
        });

        $this->info('Populating Movement data from Kardex...');
        \App\Models\Kardex::chunk(200, function ($kardexes) use ($biService) {
            foreach ($kardexes as $kardex) {
                try {
                    $biService->updateDailyMovement($kardex);
                } catch (\Exception $e) {
                    $this->error("KARDEX ERROR [{$kardex->id}]: " . $e->getMessage());
                }
            }
        });

        $this->info('Updating individual Product Stats...');
        \App\Models\Product::chunk(100, function ($products) use ($biService) {
            foreach ($products as $product) {
                $biService->updateProductStats($product->id);
            }
        });

        $this->info('Recalculating ABC Rankings...');
        $biService->recalculateABCRankings();

        $this->info('Taking Inventory snapshot...');
        $biService->takeInventorySnapshot();

        return 0;
    }
}
