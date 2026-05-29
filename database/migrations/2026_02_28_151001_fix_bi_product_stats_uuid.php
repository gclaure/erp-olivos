<?php

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
        Schema::dropIfExists('bi_product_stats');
        
        Schema::create('bi_product_stats', function (Blueprint $table) {
            $table->id();
            $table->uuid('product_id')->unique();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            
            // Aggregates
            $table->decimal('total_revenue', 15, 2)->default('0.00');
            $table->decimal('total_profit', 15, 2)->default('0.00');
            $table->decimal('total_quantity_sold', 12, 4)->default('0.0000');
            
            // Analytics
            $table->decimal('rotation_index', 8, 2)->default('0.00');
            $table->char('abc_rank', 1)->default('C');
            
            $table->decimal('last_cost', 12, 4)->default('0.0000');
            $table->decimal('avg_cost', 12, 4)->default('0.0000');
            
            $table->timestamp('last_sale_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_product_stats');
    }
};
