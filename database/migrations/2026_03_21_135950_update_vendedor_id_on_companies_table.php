<?php

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
        // Limpiar datos previos si existen (ya que apuntaban a 'users')
        DB::table('companies')->update(['vendedor_id' => null]);

        Schema::table('companies', function (Blueprint $table) {
            // Eliminar la relación anterior (Foreign key a users)
            // Usamos el nombre estándar de Laravel para mayor seguridad
            $table->dropForeign('companies_vendedor_id_foreign');
            
            // Re-vincular a la tabla asesores
            $table->foreign('vendedor_id')
                ->references('id')
                ->on('asesores')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['vendedor_id']);
            
            $table->foreign('vendedor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
