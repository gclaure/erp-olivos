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
        Schema::create('consumption_requests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('warehouse_id')->index();
            $table->uuid('user_id')->index();
            $table->string('requested_by'); // Ej: Cocina, Pastelería, Producción
            $table->date('date');
            $table->string('status')->default('pendiente')->index(); // pendiente, entregado, parcial, compras_generado, cancelado
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('consumption_request_details', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('consumption_request_id')->index();
            $table->uuid('product_id')->index();
            $table->decimal('quantity_requested', 12, 4);
            $table->decimal('quantity_delivered', 12, 4)->default('0.0000');
            $table->timestamps();

            // Foreign keys
            $table->foreign('consumption_request_id')
                ->references('id')
                ->on('consumption_requests')
                ->cascadeOnDelete();
                
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumption_request_details');
        Schema::dropIfExists('consumption_requests');
    }
};
