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
        Schema::table('products', function (Blueprint $table) {
            $table->string('location')->nullable()->after('is_active');
            $table->string('dimensions')->nullable()->after('location');
            $table->text('observations')->nullable()->after('dimensions');
            $table->string('reference_link', 500)->nullable()->after('observations');
            $table->string('slug')->nullable()->unique()->after('name');
            $table->json('drive_links')->nullable()->after('reference_link');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'location',
                'dimensions',
                'observations',
                'reference_link',
                'slug',
                'drive_links',
            ]);
        });
    }
};
