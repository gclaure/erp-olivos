<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('voucher_type')->default('sin_factura')->after('purchase_number');
            $table->string('payment_type')->default('contado')->after('voucher_type');
            $table->date('due_date')->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['voucher_type', 'payment_type', 'due_date']);
        });
    }
};
