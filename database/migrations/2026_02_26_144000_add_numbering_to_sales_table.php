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
            // branch_id matches BranchScoped expectation: branch_id, number, year
            $table->foreignUuid('branch_id')->nullable()->after('point_of_sale_id')->constrained()->nullOnDelete();
            $table->integer('number')->nullable()->after('branch_id');
            $table->integer('year')->nullable()->after('number');

            $table->unique(['branch_id', 'year', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
