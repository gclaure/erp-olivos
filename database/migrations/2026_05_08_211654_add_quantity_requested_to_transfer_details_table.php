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
        Schema::table('transfer_details', function (Blueprint $table) {
            $table->decimal('quantity_requested', 12, 4)->default(0)->after('product_id');
        });

        // Initialize quantity_requested with quantity_sent for existing records
        DB::table('transfer_details')->update([
            'quantity_requested' => DB::raw('quantity_sent')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_details', function (Blueprint $table) {
            $table->dropColumn('quantity_requested');
        });
    }
};
