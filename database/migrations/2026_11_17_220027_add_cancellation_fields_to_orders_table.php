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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 30)->default('registrado')->after('delivery_address');
            $table->timestamp('cancelled_at')->nullable()->after('status');
            $table->uuid('cancelled_by_user_id')->nullable()->after('cancelled_at');
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_user_id');

            $table->foreign('cancelled_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'cancelled_at',
                'cancelled_by_user_id',
                'cancellation_reason',
            ]);
        });
    }
};
