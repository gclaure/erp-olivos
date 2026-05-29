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
            $table->string('delivery_address')->nullable();
            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_phone')->nullable();
            $table->dateTime('delivery_at')->nullable();
            $table->text('delivery_observations')->nullable();
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->string('delivery_address')->nullable();
            $table->string('delivery_contact_name')->nullable();
            $table->string('delivery_contact_phone')->nullable();
            $table->dateTime('delivery_at')->nullable();
            $table->text('delivery_observations')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_address',
                'delivery_contact_name',
                'delivery_contact_phone',
                'delivery_at',
                'delivery_observations'
            ]);
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_address',
                'delivery_contact_name',
                'delivery_contact_phone',
                'delivery_at',
                'delivery_observations'
            ]);
        });
    }
};
