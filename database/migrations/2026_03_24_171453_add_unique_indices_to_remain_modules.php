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
        // Los índices únicos para quotations, purchases, y transfers ya fueron
        // establecidos correctamente en sus respectivas migraciones iniciales.
        // quotations (branch_id, year, number) -> agregado en 2026_02_26_185612
        // purchases (purchase_number unique) -> agregado en 2026_02_24_041150
        // transfers (number unique global) -> agregado en 2026_02_27_040417
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay operaciones que deshacer para esta migración vacía.
    }
};
