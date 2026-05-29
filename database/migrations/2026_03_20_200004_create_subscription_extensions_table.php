<?php

declare(strict_types=1);

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_extensions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignIdFor(Subscription::class)->constrained()->cascadeOnDelete();
            $table->integer('days_added');
            $table->string('type');
            $table->text('reason')->nullable();
            $table->uuid('added_by')->nullable();
            $table->timestamps();

            $table->foreign('added_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_extensions');
    }
};
