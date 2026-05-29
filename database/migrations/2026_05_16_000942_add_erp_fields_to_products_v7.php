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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('units_per_package', 12, 4)->default(1)->after('unit_of_measure_id');
            $table->date('expiration_date')->nullable()->after('units_per_package');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['units_per_package', 'expiration_date']);
        });
    }
};
