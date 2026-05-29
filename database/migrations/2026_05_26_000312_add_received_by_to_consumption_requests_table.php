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
        Schema::table('consumption_requests', function (Blueprint $table) {
            $table->uuid('received_by_user_id')->nullable()->index();
            $table->timestamp('received_at')->nullable();

            $table->foreign('received_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consumption_requests', function (Blueprint $table) {
            $table->dropForeign(['received_by_user_id']);
            $table->dropColumn(['received_by_user_id', 'received_at']);
        });
    }
};
