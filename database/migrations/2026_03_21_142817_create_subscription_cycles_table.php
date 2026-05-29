<?php

declare(strict_types=1);

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignIdFor(Subscription::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Plan::class)->constrained()->restrictOnDelete();
            
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('status')->default('PENDING'); // PENDING, PAID, OVERDUE
            $table->uuid('payment_id')->nullable(); // Link to payments table
            
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_cycles');
    }
};
