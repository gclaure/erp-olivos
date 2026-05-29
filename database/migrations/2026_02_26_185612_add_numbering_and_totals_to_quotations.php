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
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignUuid('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('number')->nullable();
            $table->integer('year')->nullable();
            $table->decimal('subtotal', 12, 4)->default('0.0000');
            $table->decimal('global_discount', 12, 4)->default('0.0000');
            $table->decimal('total_payment', 12, 4)->default('0.0000');
            $table->date('valid_until')->nullable();

            $table->unique(['branch_id', 'year', 'number']);
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'year', 'number']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'branch_id', 'number', 'year', 'subtotal', 
                'global_discount', 'total_payment', 'valid_until'
            ]);
        });
    }
};
