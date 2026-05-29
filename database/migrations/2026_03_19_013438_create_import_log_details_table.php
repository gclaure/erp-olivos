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
        Schema::create('import_log_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(\App\Models\ImportLog::class)->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->string('status'); // 'success' or 'error'
            $table->text('message')->nullable(); // detail of error
            $table->json('row_data')->nullable(); // the original array from Excel
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_log_details');
    }
};
