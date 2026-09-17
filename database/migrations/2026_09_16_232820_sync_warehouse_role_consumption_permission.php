<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Resetear la caché de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Garantizar la existencia del permiso manage-consumption
        $manageConsumption = Permission::firstOrCreate([
            'name' => 'manage-consumption',
            'guard_name' => 'web',
        ]);

        // 3. Permisos operativos asignados al rol Almacén
        $warehousePermissions = [
            'manage-consumption',
            'manage-products',
            'manage-categories',
            'manage-warehouses',
            'manage-inventory',
            'manage-purchases',
            'manage-providers',
        ];

        foreach ($warehousePermissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // 4. Asignar los permisos al rol Almacén de forma idempotente
        $warehouseRoles = Role::whereIn('name', ['Almacén', 'almacen', 'Almacen'])->get();
        foreach ($warehouseRoles as $role) {
            foreach ($warehousePermissions as $permName) {
                if (!$role->hasPermissionTo($permName)) {
                    $role->givePermissionTo($permName);
                }
            }
        }

        // 5. Refrescar la caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $warehouseRoles = Role::whereIn('name', ['Almacén', 'almacen', 'Almacen'])->get();
        foreach ($warehouseRoles as $role) {
            if ($role->hasPermissionTo('manage-consumption')) {
                $role->revokePermissionTo('manage-consumption');
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
