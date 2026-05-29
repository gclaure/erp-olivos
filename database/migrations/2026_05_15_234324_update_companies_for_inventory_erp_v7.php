<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('inventory_method')->default('PROMEDIO_PONDERADO')->after('receipt_type');
            $table->boolean('has_inventory_movements')->default(false)->after('inventory_method');
            $table->date('inventories_closed_until')->nullable()->after('has_inventory_movements');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['inventory_method', 'has_inventory_movements', 'inventories_closed_until']);
        });
    }
};
