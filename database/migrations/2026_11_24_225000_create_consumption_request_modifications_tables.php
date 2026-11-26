<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumption_request_modifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('consumption_request_id');
            $table->uuid('consumption_request_detail_id');
            $table->uuid('from_product_id');
            $table->uuid('to_product_id');
            $table->decimal('original_quantity', 12, 4);
            $table->decimal('requested_quantity', 12, 4);
            $table->decimal('quantity_dispatched_before_modification', 12, 4)->default(0);
            $table->decimal('quantity_prepared', 12, 4)->default(0);
            $table->decimal('quantity_physically_delivered', 12, 4)->default(0);
            $table->text('reason');
            $table->string('status', 30)->default('pendiente');
            $table->uuid('requested_by_user_id');
            $table->uuid('resolved_by_user_id')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->jsonb('purchase_resolutions')->nullable();
            $table->uuid('cancelled_by_user_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->foreign('consumption_request_id')
                ->references('id')
                ->on('consumption_requests')
                ->restrictOnDelete();

            $table->foreign('consumption_request_detail_id')
                ->references('id')
                ->on('consumption_request_details')
                ->restrictOnDelete();

            $table->foreign('from_product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('to_product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();

            $table->foreign('requested_by_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('resolved_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('cancelled_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index('consumption_request_id');
            $table->index('consumption_request_detail_id');
            $table->index('from_product_id');
            $table->index('to_product_id');
            $table->index('status');
            $table->index('requested_by_user_id');
            $table->index('resolved_by_user_id');
            $table->index('cancelled_by_user_id');
        });

        Schema::create('consumption_request_modification_returns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('consumption_request_modification_id');
            $table->decimal('returned_quantity', 12, 4);
            $table->uuid('returned_by_user_id');
            $table->timestamp('returned_at');
            $table->text('return_notes')->nullable();
            $table->timestamps();

            $table->foreign('consumption_request_modification_id')
                ->references('id')
                ->on('consumption_request_modifications')
                ->restrictOnDelete();

            $table->foreign('returned_by_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index('consumption_request_modification_id');
            $table->index('returned_by_user_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX idx_crm_open_detail
                ON consumption_request_modifications (consumption_request_detail_id)
                WHERE status IN ('pendiente', 'aprobada');
            SQL);

            DB::statement(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_immutable_crm_changes()
                RETURNS TRIGGER AS $$
                BEGIN
                    IF TG_OP = 'DELETE' THEN
                        RAISE EXCEPTION 'El historial de modificaciones de consumo es inmutable y no puede eliminarse.';
                    END IF;

                    IF TG_OP = 'UPDATE' AND OLD.status IN ('aplicada', 'rechazada', 'cancelada') THEN
                        RAISE EXCEPTION 'Una modificación en estado final (%) es inmutable y no puede modificarse.', OLD.status;
                    END IF;

                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            SQL);

            DB::statement(<<<'SQL'
                CREATE TRIGGER trg_prevent_immutable_crm_changes
                BEFORE UPDATE OR DELETE ON consumption_request_modifications
                FOR EACH ROW EXECUTE FUNCTION prevent_immutable_crm_changes();
            SQL);

            DB::statement(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_immutable_crm_return_changes()
                RETURNS TRIGGER AS $$
                BEGIN
                    RAISE EXCEPTION 'Los registros de devolución física son inmutables y no pueden editarse ni eliminarse.';
                END;
                $$ LANGUAGE plpgsql;
            SQL);

            DB::statement(<<<'SQL'
                CREATE TRIGGER trg_prevent_immutable_crm_return_changes
                BEFORE UPDATE OR DELETE ON consumption_request_modification_returns
                FOR EACH ROW EXECUTE FUNCTION prevent_immutable_crm_return_changes();
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_immutable_crm_return_changes ON consumption_request_modification_returns;');
            DB::statement('DROP FUNCTION IF EXISTS prevent_immutable_crm_return_changes();');
            DB::statement('DROP TRIGGER IF EXISTS trg_prevent_immutable_crm_changes ON consumption_request_modifications;');
            DB::statement('DROP FUNCTION IF EXISTS prevent_immutable_crm_changes();');
            DB::statement('DROP INDEX IF EXISTS idx_crm_open_detail;');
        }

        Schema::dropIfExists('consumption_request_modification_returns');
        Schema::dropIfExists('consumption_request_modifications');
    }
};
