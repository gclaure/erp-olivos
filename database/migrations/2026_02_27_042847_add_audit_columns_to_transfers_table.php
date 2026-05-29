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
        Schema::table('transfers', function (Blueprint $table) {
            $table->uuid('approved_by_id')->nullable();
            $table->uuid('shipped_by_id')->nullable();
            $table->uuid('received_by_id')->nullable();

            $table->foreign('approved_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('shipped_by_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('received_by_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['approved_by_id']);
            $table->dropForeign(['shipped_by_id']);
            $table->dropForeign(['received_by_id']);
            $table->dropColumn(['approved_by_id', 'shipped_by_id', 'received_by_id']);
        });
    }
};
