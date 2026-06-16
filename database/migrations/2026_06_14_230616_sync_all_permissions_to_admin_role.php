<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use App\Models\Role;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = Permission::all();

        $adminRole = Role::where('name', 'Administrador')->first();
        if ($adminRole) {
            $adminRole->syncPermissions($allPermissions);
        }

        $adminLowerRole = Role::where('name', 'admin')->first();
        if ($adminLowerRole) {
            $adminLowerRole->syncPermissions($allPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
