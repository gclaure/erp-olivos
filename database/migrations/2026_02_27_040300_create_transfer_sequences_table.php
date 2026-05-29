<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_sequences', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('branch_id');
            $table->integer('year');
            $table->integer('last_number')->default(0);
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->unique(['branch_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_sequences');
    }
};
