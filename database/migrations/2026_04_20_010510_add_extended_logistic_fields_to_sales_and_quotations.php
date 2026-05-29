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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('delivery_zone')->nullable();
            $table->text('delivery_reference')->nullable();
            $table->decimal('delivery_cost', 12, 4)->default(0);
            $table->string('delivery_time_slot')->nullable(); // 'mañana', 'tarde', 'noche'
            $table->text('delivery_map_url')->nullable();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('delivery_zone')->nullable();
            $table->text('delivery_reference')->nullable();
            $table->decimal('delivery_cost', 12, 4)->default(0);
            $table->string('delivery_time_slot')->nullable();
            $table->text('delivery_map_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_zone',
                'delivery_reference',
                'delivery_cost',
                'delivery_time_slot',
                'delivery_map_url',
            ]);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_zone',
                'delivery_reference',
                'delivery_cost',
                'delivery_time_slot',
                'delivery_map_url',
            ]);
        });
    }
};
