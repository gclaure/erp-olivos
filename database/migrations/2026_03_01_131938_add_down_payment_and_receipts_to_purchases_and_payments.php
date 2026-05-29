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
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('down_payment', 12, 4)->default('0.0000')->after('total');
            $table->string('receipt_path')->nullable()->after('down_payment');
        });

        Schema::table('account_payable_payments', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['down_payment', 'receipt_path']);
        });

        Schema::table('account_payable_payments', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });
    }
};
