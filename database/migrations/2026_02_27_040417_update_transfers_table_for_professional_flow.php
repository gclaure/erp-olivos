<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->string('number')->nullable()->unique()->after('id');
            $table->uuid('origin_branch_id')->nullable()->after('number');
            $table->uuid('destination_branch_id')->nullable()->after('origin_branch_id');
            
            $table->foreign('origin_branch_id')->references('id')->on('branches');
            $table->foreign('destination_branch_id')->references('id')->on('branches');
            
            // Rename columns if they exist as from/to
            $table->renameColumn('from_warehouse_id', 'origin_warehouse_id');
            $table->renameColumn('to_warehouse_id', 'destination_warehouse_id');
            
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('status')->default('draft')->change();
        });

        Schema::table('transfer_details', function (Blueprint $table) {
            $table->renameColumn('quantity', 'quantity_sent');
            $table->decimal('quantity_received', 12, 4)->default(0)->after('quantity');
            $table->decimal('cost', 12, 4)->default(0)->after('quantity_received');
            $table->decimal('quantity_sent', 12, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumn(['number', 'origin_branch_id', 'destination_branch_id', 'shipped_at', 'received_at']);
            $table->renameColumn('origin_warehouse_id', 'from_warehouse_id');
            $table->renameColumn('destination_warehouse_id', 'to_warehouse_id');
        });

        Schema::table('transfer_details', function (Blueprint $table) {
            $table->renameColumn('quantity_sent', 'quantity');
            $table->dropColumn(['quantity_received', 'cost']);
        });
    }
};
