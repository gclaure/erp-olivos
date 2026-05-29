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
        Schema::create('bi_movements_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->uuid('branch_id')->index();
            $table->uuid('warehouse_id')->index();
            $table->uuid('product_id')->index();
            
            $table->decimal('total_input_quantity', 12, 4)->default('0.0000');
            $table->decimal('total_output_quantity', 12, 4)->default('0.0000');
            $table->decimal('input_cost_value', 15, 2)->default('0.00'); // Valor monetario de entradas
            $table->decimal('output_cost_value', 15, 2)->default('0.00'); // Valor monetario de salidas

            $table->unique(['date', 'branch_id', 'warehouse_id', 'product_id'], 'bi_movements_daily_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_movements_daily');
    }
};
