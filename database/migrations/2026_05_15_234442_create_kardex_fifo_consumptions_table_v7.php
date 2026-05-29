<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kardex_fifo_consumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('warehouse_id');
            
            $table->uuid('input_kardex_id');
            $table->uuid('output_kardex_id');
            
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('total_cost', 15, 2);
            
            $table->string('status')->default('ACTIVE'); // ACTIVE, REVERSED
            
            $table->timestamps();

            // FKs
            $table->foreign('input_kardex_id')->references('id')->on('kardexes')->cascadeOnDelete();
            $table->foreign('output_kardex_id')->references('id')->on('kardexes')->cascadeOnDelete();
            
            // Indices
            $table->index(['product_id', 'warehouse_id'], 'idx_fifo_consump_search');
            $table->index('output_kardex_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kardex_fifo_consumptions');
    }
};
