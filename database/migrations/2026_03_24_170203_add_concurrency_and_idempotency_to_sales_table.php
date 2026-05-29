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
            if (!Schema::hasColumn('sales', 'year')) {
                $table->integer('year')->nullable()->after('date');
            }
            if (!Schema::hasColumn('sales', 'request_id')) {
                $table->uuid('request_id')->nullable()->unique()->after('number');
            }
            
            // Índice único para asegurar que la numeración no se repita por sucursal y año
            $table->unique(['branch_id', 'year', 'number'], 'sales_branch_year_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_branch_year_number_unique');
            $table->dropColumn(['request_id', 'year']);
        });
    }
};
