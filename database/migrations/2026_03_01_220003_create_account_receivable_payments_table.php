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
        Schema::create('account_receivable_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_receivable_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 4);
            $table->date('payment_date')->index();
            $table->string('payment_method', 50);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_receivable_payments');
    }
};
