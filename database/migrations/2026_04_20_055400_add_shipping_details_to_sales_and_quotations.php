<?php

declare(strict_types=1);

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
            $table->string('shipping_company')->nullable();
            $table->string('shipping_origin')->nullable();
            $table->string('shipping_destination')->nullable();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('shipping_company')->nullable();
            $table->string('shipping_origin')->nullable();
            $table->string('shipping_destination')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['shipping_company', 'shipping_origin', 'shipping_destination']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['shipping_company', 'shipping_origin', 'shipping_destination']);
        });
    }
};
