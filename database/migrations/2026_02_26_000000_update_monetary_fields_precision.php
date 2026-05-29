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
            $table->decimal('price', 12, 4)->default(0)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total', 15, 2)->change();
            $table->decimal('subtotal', 15, 2)->default(0)->change();
            $table->decimal('global_discount', 12, 4)->default(0)->change();
            $table->decimal('total_payment', 15, 2)->default(0)->change();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('unit_price', 12, 4)->change();
            $table->decimal('discount', 12, 4)->default(0)->change();
            $table->decimal('subtotal', 15, 2)->change();
        });

        Schema::table('kardexes', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('unit_cost', 12, 4)->default(0)->change();
            $table->decimal('total_cost', 15, 2)->default(0)->change();
            $table->decimal('balance_quantity', 12, 4)->change();
            $table->decimal('balance_total_cost', 15, 2)->default(0)->change();
            $table->decimal('avg_cost', 12, 4)->default(0)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total', 15, 2)->change();
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('unit_price', 12, 4)->change();
            $table->decimal('subtotal', 15, 2)->change();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('total', 15, 2)->change();
        });

        Schema::table('quotation_details', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('unit_price', 12, 4)->change();
            $table->decimal('subtotal', 15, 2)->change();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting to previous state (mostly 12, 2)
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->change();
            $table->decimal('subtotal', 12, 2)->default(0)->change();
            $table->decimal('global_discount', 12, 2)->default(0)->change();
            $table->decimal('total_payment', 12, 2)->default(0)->change();
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
            $table->decimal('unit_price', 12, 2)->change();
            $table->decimal('discount', 12, 2)->default(0)->change();
            $table->decimal('subtotal', 12, 2)->change();
        });

        Schema::table('kardexes', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
            $table->decimal('unit_cost', 12, 2)->default(0)->change();
            $table->decimal('total_cost', 12, 2)->default(0)->change();
            $table->decimal('balance_quantity', 12, 2)->change();
            $table->decimal('balance_total_cost', 12, 2)->default(0)->change();
            $table->decimal('avg_cost', 12, 2)->default(0)->change();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->change();
        });

        Schema::table('purchase_details', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
            $table->decimal('unit_price', 12, 2)->change();
            $table->decimal('subtotal', 12, 2)->change();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->change();
        });

        Schema::table('quotation_details', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
            $table->decimal('unit_price', 12, 2)->change();
            $table->decimal('subtotal', 12, 2)->change();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->change();
        });
    }
};
