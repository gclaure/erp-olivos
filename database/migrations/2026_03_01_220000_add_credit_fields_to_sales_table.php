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
        Schema::table('sales', function (Blueprint $column) {
            $column->string('payment_type', 20)->default('efectivo')->index();
            $column->integer('credit_days')->nullable();
            $column->date('due_date')->nullable();
            $column->decimal('balance', 12, 4)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $column) {
            $column->dropColumn(['payment_type', 'credit_days', 'due_date', 'balance']);
        });
    }
};
