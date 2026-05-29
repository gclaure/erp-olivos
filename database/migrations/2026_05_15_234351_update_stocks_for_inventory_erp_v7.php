<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('inventory_value', 15, 2)->default(0)->after('quantity');
            $table->decimal('average_cost', 12, 4)->default(0)->after('inventory_value');
            $table->integer('version')->default(1)->after('average_cost');
            
            // Unique constraint for multitenant stock aggregation
            $table->unique(['product_id', 'warehouse_id'], 'idx_stocks_unique_tenant_product_warehouse');
            
            // Indices for performance
        });

        // Postgres specific check constraints for ERP integrity
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE stocks ADD CONSTRAINT check_quantity_non_negative CHECK (quantity >= 0)');
            DB::statement('ALTER TABLE stocks ADD CONSTRAINT check_inventory_value_non_negative CHECK (inventory_value >= 0)');
        }
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('idx_stocks_unique_tenant_product_warehouse');
        });
        
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE stocks DROP CONSTRAINT IF EXISTS check_quantity_non_negative');
            DB::statement('ALTER TABLE stocks DROP CONSTRAINT IF EXISTS check_inventory_value_non_negative');
        }
    }
};
