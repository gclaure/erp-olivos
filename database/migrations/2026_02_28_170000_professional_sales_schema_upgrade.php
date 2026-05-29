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
            $table->decimal('total_gross', 15, 2)->default(0)->after('notes');
            $table->decimal('total_discount', 15, 2)->default(0)->after('total_gross');
            $table->decimal('total_tax', 15, 2)->default(0)->after('total_discount');
            $table->decimal('total_cost', 18, 6)->default(0)->after('total_tax');
            $table->decimal('total_profit', 18, 6)->default(0)->after('total_cost');
            $table->integer('items_count')->default(0)->after('total_profit');
            $table->decimal('total_quantity', 12, 4)->default(0)->after('items_count');
            $table->boolean('is_loss')->default(false)->index()->after('total_quantity');
            $table->string('cost_method')->default('AVERAGE')->after('is_loss');
            $table->char('currency_code', 3)->default('BOB')->after('cost_method');
            $table->decimal('exchange_rate', 12, 4)->default(1)->after('currency_code');
            
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->index('date');
            $table->index('status');
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 6)->default(0)->after('unit_price');
            $table->decimal('unit_profit', 18, 6)->default(0)->after('unit_cost');
            $table->decimal('profit_margin_percent', 8, 4)->default(0)->after('unit_profit');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('profit_margin_percent');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('discount_amount');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('tax_amount');
            $table->boolean('is_loss')->default(false)->after('subtotal');
            
            $table->index('product_id');
            $table->index('unit_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['cancelled_by']);
            
            $table->dropColumn([
                'total_gross', 'total_discount', 'total_tax', 'total_cost', 'total_profit',
                'items_count', 'total_quantity', 'is_loss', 'cost_method', 'currency_code',
                'exchange_rate', 'created_by', 'updated_by', 'cancelled_by', 'cancelled_at'
            ]);
            // $table->dropIndex(['date']);
            // $table->dropIndex(['status']);
        });

        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn([
                'unit_cost', 'unit_profit', 'profit_margin_percent', 'discount_amount',
                'tax_amount', 'tax_percent', 'is_loss'
            ]);
            // Comentamos estas líneas porque si el índice no existe, el rollback falla.
            // Al hacer migrate:refresh, esto no es crítico ya que la tabla se borrará luego.
            // $table->dropIndex(['product_id']);
            // $table->dropIndex(['unit_profit']);
        });
    }
};
