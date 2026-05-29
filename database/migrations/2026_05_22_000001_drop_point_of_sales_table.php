<?php

declare(strict_types=1);

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
            // Verificar si la columna existe antes de intentar eliminar la FK y la columna
            if (Schema::hasColumn('sales', 'point_of_sale_id')) {
                $table->dropForeign(['point_of_sale_id']);
                $table->dropColumn('point_of_sale_id');
            }
        });

        Schema::table('kardexes', function (Blueprint $table) {
            // Verificar si la columna existe antes de intentar eliminar la FK y la columna
            if (Schema::hasColumn('kardexes', 'point_of_sale_id')) {
                $table->dropForeign(['point_of_sale_id']);
                $table->dropColumn('point_of_sale_id');
            }
        });

        Schema::dropIfExists('point_of_sale_user');
        Schema::dropIfExists('point_of_sales');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esta migración no es reversible de forma automática
    }
};
