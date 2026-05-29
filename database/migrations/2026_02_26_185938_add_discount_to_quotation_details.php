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
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->decimal('discount', 12, 4)->default('0.0000')->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
