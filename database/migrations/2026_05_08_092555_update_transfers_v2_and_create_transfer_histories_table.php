<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar tabla de transferencias
        Schema::table('transfers', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable();
            $table->uuid('rejected_by_id')->nullable();
            $table->text('rejection_reason')->nullable();
            
            $table->timestamp('cancelled_at')->nullable();
            $table->uuid('cancelled_by_id')->nullable();

            $table->foreign('rejected_by_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by_id')->references('id')->on('users')->onDelete('set null');
        });

        // Crear tabla de historial de transferencias
        Schema::create('transfer_histories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('transfer_id');
            $table->uuid('user_id');
            $table->uuid('warehouse_id')->nullable();
            $table->string('action'); // solicitud, aprobacion, rechazo, despacho, recepcion, cancelacion
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->jsonb('metadata')->nullable(); // Snapshots
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('transfers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            
            $table->index('transfer_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_histories');
        
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropForeign(['rejected_by_id']);
            $table->dropForeign(['cancelled_by_id']);
            $table->dropColumn([
                'rejected_at', 
                'rejected_by_id', 
                'rejection_reason',
                'cancelled_at',
                'cancelled_by_id'
            ]);
        });
    }
};
