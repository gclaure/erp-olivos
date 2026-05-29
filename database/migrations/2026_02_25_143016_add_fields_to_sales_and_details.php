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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignUuid('point_of_sale_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->after('total')->default(0);
            $table->decimal('global_discount', 12, 2)->after('subtotal')->default(0);
            $table->decimal('total_payment', 12, 2)->after('global_discount')->default(0);
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->after('unit_price')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['point_of_sale_id']);
            $table->dropColumn(['point_of_sale_id', 'subtotal', 'global_discount', 'total_payment']);
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
