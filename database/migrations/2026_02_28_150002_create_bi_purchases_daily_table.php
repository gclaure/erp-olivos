<?php

use App\Models\Product;
use App\Models\Provider;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bi_purchases_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignIdFor(Provider::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Warehouse::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            
            $table->decimal('total_quantity', 12, 4)->default('0.0000');
            $table->decimal('total_cost', 15, 2)->default('0.00');

            $table->unique(['date', 'provider_id', 'warehouse_id', 'product_id'], 'bi_purchases_daily_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_purchases_daily');
    }
};
