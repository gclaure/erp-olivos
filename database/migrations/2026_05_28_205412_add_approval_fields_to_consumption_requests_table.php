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
        Schema::table('consumption_requests', function (Blueprint $table) {
            $table->uuid('approved_by_user_id')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            
            $table->uuid('observed_by_user_id')->nullable()->index();
            $table->timestamp('observed_at')->nullable();
            
            $table->text('observation_notes')->nullable();

            $table->foreign('approved_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('observed_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumption_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropForeign(['observed_by_user_id']);
            
            $table->dropColumn([
                'approved_by_user_id',
                'approved_at',
                'observed_by_user_id',
                'observed_at',
                'observation_notes'
            ]);
        });
    }
};

