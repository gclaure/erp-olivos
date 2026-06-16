<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'area')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('area', 150)->nullable()->after('branch_id');
            });
        }

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure required roles exist
        Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Almacén', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Consumidor', 'guard_name' => 'web']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'area')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('area');
            });
        }
    }
};
