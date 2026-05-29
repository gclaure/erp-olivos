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
        Schema::table('notifications', function (Blueprint $table) {
            // Drop existing morphs parts
            $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
            $table->dropColumn('notifiable_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            // Re-add as uuid
            $table->uuid('notifiable_id')->after('notifiable_type');
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
            $table->dropColumn('notifiable_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            // Permitimos nullable para que el rollback no falle con datos existentes durante el refresh
            $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }
};
