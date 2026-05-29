<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kardexes', function (Blueprint $table) {
            $table->uuid('operation_uuid')->nullable()->after('id');
            $table->uuid('source_operation_uuid')->nullable()->after('operation_uuid');
            $table->string('movement_type')->nullable()->after('type');
            $table->string('reference_type')->nullable()->after('movement_type');
            $table->uuid('reference_id')->nullable()->after('reference_type');
            
            $table->timestamp('movement_date')->nullable()->after('reference_id');
            $table->timestamp('accounting_date')->nullable()->after('movement_date');
            $table->timestamp('processed_at')->nullable()->after('accounting_date');
            $table->timestamp('queued_at')->nullable()->after('processed_at');
            
            $table->decimal('balance_after', 12, 4)->nullable()->after('balance_quantity');
            $table->decimal('value_after', 15, 2)->nullable()->after('balance_after');
            
            $table->decimal('remaining_quantity', 12, 4)->nullable()->after('value_after');
            $table->boolean('is_fifo_layer')->default(false)->after('remaining_quantity');

            // FKs
            
            // Unique index for idempotency
            $table->unique(['operation_uuid'], 'idx_kardex_unique_operation');
            if (config('database.default') !== 'pgsql') {
                $table->index(['product_id', 'warehouse_id', 'movement_date', 'id'], 'idx_kardex_fifo_general');
            }
        });

        // Split raw statements to ensure columns exist
        if (config('database.default') === 'pgsql') {
            DB::statement('CREATE INDEX idx_kardex_fifo_layers ON kardexes (product_id, warehouse_id, movement_date, id) WHERE remaining_quantity > 0');
            
            // Integrity check for remaining_quantity
            DB::statement('ALTER TABLE kardexes ADD CONSTRAINT check_remaining_non_negative CHECK (remaining_quantity >= 0 OR remaining_quantity IS NULL)');
            
            // Reference integrity check
            DB::statement('ALTER TABLE kardexes ADD CONSTRAINT check_reference_pair CHECK ((reference_type IS NULL AND reference_id IS NULL) OR (reference_type IS NOT NULL AND reference_id IS NOT NULL))');
        }
    }

    public function down(): void
    {
        Schema::table('kardexes', function (Blueprint $table) {
            $table->dropUnique('idx_kardex_unique_operation');
            
            if (config('database.default') === 'pgsql') {
                DB::statement('DROP INDEX IF EXISTS idx_kardex_fifo_layers');
                DB::statement('ALTER TABLE kardexes DROP CONSTRAINT IF EXISTS check_remaining_non_negative');
                DB::statement('ALTER TABLE kardexes DROP CONSTRAINT IF EXISTS check_reference_pair');
            } else {
                $table->dropIndex('idx_kardex_fifo_general');
            }

            $table->dropColumn([
                'operation_uuid', 'source_operation_uuid', 'movement_type',
                'reference_type', 'reference_id', 'movement_date', 'accounting_date',
                'processed_at', 'queued_at', 'balance_after', 'value_after',
                'remaining_quantity', 'is_fifo_layer'
            ]);
        });
    }
};
