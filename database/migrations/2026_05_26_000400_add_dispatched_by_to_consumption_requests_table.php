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
            $table->uuid('dispatched_by_user_id')->nullable()->index();
            $table->timestamp('dispatched_at')->nullable();

            $table->foreign('dispatched_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumption_requests', function (Blueprint $table) {
            $table->dropForeign(['dispatched_by_user_id']);
            $table->dropColumn(['dispatched_by_user_id', 'dispatched_at']);
        });
    }
};
