<?php

declare(strict_types=1);

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('BOB');
            $table->timestamp('paid_at')->nullable();
            $table->string('method');
            $table->string('reference')->nullable();
            $table->string('status')->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
