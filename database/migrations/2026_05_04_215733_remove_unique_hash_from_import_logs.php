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
        Schema::table('import_logs', function (Blueprint $table) {
            // Eliminamos la restricción que bloquea la re-importación del mismo archivo
            $table->dropUnique('import_logs_file_hash_unique');
            $table->string('file_hash', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            // Al hacer rollback, no podemos re-activar la restricción unique si ya existen duplicados
            // $table->unique('file_hash');
            $table->string('file_hash', 64)->nullable()->change();
        });
    }
};
