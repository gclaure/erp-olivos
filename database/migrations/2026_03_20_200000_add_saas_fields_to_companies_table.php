<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('ACTIVE')->after('logo_path');
            $table->uuid('owner_user_id')->nullable()->after('status');
            $table->timestamp('trial_ends_at')->nullable()->after('owner_user_id');

            $table->index('status');

            $table->foreign('owner_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'owner_user_id', 'trial_ends_at']);
        });
    }
};
