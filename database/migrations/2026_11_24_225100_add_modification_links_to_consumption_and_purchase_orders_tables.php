<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumption_request_details', function (Blueprint $table): void {
            $table->string('fulfillment_status', 30)
                ->default('normal')
                ->after('quantity_received');

            $table->index('fulfillment_status');
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->uuid('consumption_request_id')
                ->nullable()
                ->after('user_id');

            $table->foreign('consumption_request_id')
                ->references('id')
                ->on('consumption_requests')
                ->restrictOnDelete();

            $table->index('consumption_request_id');
        });

        Schema::table('purchase_order_details', function (Blueprint $table): void {
            $table->uuid('consumption_request_detail_id')
                ->nullable()
                ->after('product_id');
            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('subtotal');
            $table->text('cancellation_reason')
                ->nullable()
                ->after('cancelled_at');

            $table->foreign('consumption_request_detail_id')
                ->references('id')
                ->on('consumption_request_details')
                ->restrictOnDelete();

            $table->index('consumption_request_detail_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_details', function (Blueprint $table): void {
            $table->dropForeign(['consumption_request_detail_id']);
            $table->dropIndex(['consumption_request_detail_id']);
            $table->dropColumn(['consumption_request_detail_id', 'cancelled_at', 'cancellation_reason']);
        });

        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropForeign(['consumption_request_id']);
            $table->dropIndex(['consumption_request_id']);
            $table->dropColumn('consumption_request_id');
        });

        Schema::table('consumption_request_details', function (Blueprint $table): void {
            $table->dropIndex(['fulfillment_status']);
            $table->dropColumn('fulfillment_status');
        });
    }
};
