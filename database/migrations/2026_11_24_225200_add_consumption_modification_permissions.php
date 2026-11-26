<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $requestPerm = Permission::firstOrCreate([
            'name' => 'request-consumption-modification',
            'guard_name' => 'web',
        ]);

        $approvePerm = Permission::firstOrCreate([
            'name' => 'approve-consumption-modification',
            'guard_name' => 'web',
        ]);

        $warehouseRoles = Role::whereIn('name', ['Almacén', 'almacen', 'Almacen'])->get();
        foreach ($warehouseRoles as $role) {
            if (!$role->hasPermissionTo($requestPerm)) {
                $role->givePermissionTo($requestPerm);
            }
        }

        $adminRoles = Role::whereIn('name', ['Administrador', 'Admin', 'administrador', 'admin'])->get();
        foreach ($adminRoles as $role) {
            if (!$role->hasPermissionTo($requestPerm)) {
                $role->givePermissionTo($requestPerm);
            }
            if (!$role->hasPermissionTo($approvePerm)) {
                $role->givePermissionTo($approvePerm);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $warehouseRoles = Role::whereIn('name', ['Almacén', 'almacen', 'Almacen'])->get();
        foreach ($warehouseRoles as $role) {
            if ($role->hasPermissionTo('request-consumption-modification')) {
                $role->revokePermissionTo('request-consumption-modification');
            }
        }

        $adminRoles = Role::whereIn('name', ['Administrador', 'Admin', 'administrador', 'admin'])->get();
        foreach ($adminRoles as $role) {
            if ($role->hasPermissionTo('request-consumption-modification')) {
                $role->revokePermissionTo('request-consumption-modification');
            }
            if ($role->hasPermissionTo('approve-consumption-modification')) {
                $role->revokePermissionTo('approve-consumption-modification');
            }
        }

        Permission::whereIn('name', [
            'request-consumption-modification',
            'approve-consumption-modification',
        ])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
