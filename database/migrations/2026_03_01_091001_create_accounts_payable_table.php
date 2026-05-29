<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('purchase_id');
            $table->foreign('purchase_id')->references('id')->on('purchases')->cascadeOnDelete();
            $table->uuid('provider_id');
            $table->foreign('provider_id')->references('id')->on('providers')->cascadeOnDelete();
            $table->decimal('total_amount', 12, 4)->default(0);
            $table->decimal('paid_amount', 12, 4)->default(0);
            $table->decimal('balance', 12, 4)->default(0);
            $table->date('due_date');
            $table->string('status')->default('PENDING'); // PENDING, PARTIAL, PAID, OVERDUE
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_payable');
    }
};
