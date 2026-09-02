<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Renombrar create-sales a create-consumption si existe
        $createSalesPerm = Permission::where('name', 'create-sales')->first();
        if ($createSalesPerm) {
            $createSalesPerm->update(['name' => 'create-consumption']);
        } else {
            Permission::firstOrCreate(['name' => 'create-consumption', 'guard_name' => 'web']);
        }

        // 2. Crear manage-consumption si no existe
        Permission::firstOrCreate(['name' => 'manage-consumption', 'guard_name' => 'web']);

        // 3. Definir la lista oficial de 14 permisos vigentes
        $validPermissions = [
            'create-consumption',
            'manage-consumption',
            'manage-inventory',
            'manage-products',
            'manage-categories',
            'manage-warehouses',
            'create-purchases',
            'manage-purchases',
            'manage-providers',
            'manage-users',
            'manage-roles',
            'manage-company',
            'manage-branches',
            'view-reports',
        ];

        foreach ($validPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 4. Eliminar permisos obsoletos
        $obsoletePermissions = [
            'manage-clients',
            'manage-deliveries',
            'manage-pos',
            'pos-access',
            'manage-sales',
            'manage-transfers',
            'manage-settings',
            'pos-delivery-pickup',
            'pos-delivery-home',
            'pos-delivery-point',
            'pos-delivery-package',
        ];

        // Remover relaciones huérfanas y eliminar permisos obsoletos
        $obsoleteIds = Permission::whereIn('name', $obsoletePermissions)->pluck('id');
        if ($obsoleteIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $obsoleteIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $obsoleteIds)->delete();
            Permission::whereIn('id', $obsoleteIds)->delete();
        }

        // 5. Asignar permisos por defecto a los roles existentes
        // A) Consumidor
        $consumidorRoles = Role::whereIn('name', ['Consumidor', 'consumidor'])->get();
        foreach ($consumidorRoles as $role) {
            $role->syncPermissions(['create-consumption']);
        }

        // B) Almacén
        $almacenRoles = Role::whereIn('name', ['Almacén', 'almacen'])->get();
        foreach ($almacenRoles as $role) {
            $role->syncPermissions([
                'manage-products',
                'manage-categories',
                'manage-warehouses',
                'manage-inventory',
                'manage-consumption',
                'manage-purchases',
                'manage-providers',
            ]);
        }

        // C) Administrador / Super Admin
        $allActivePermissions = Permission::whereIn('name', $validPermissions)->get();
        $adminRoles = Role::whereIn('name', [
            'Admin', 'Administrador', 'admin', 'administrador',
            'Super Admin', 'Super-admin', 'Super Administrador', 'super-admin', 'super administrador', 'Superadmin'
        ])->get();

        foreach ($adminRoles as $role) {
            $role->syncPermissions($allActivePermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::where('name', 'create-consumption')->first();
        if ($perm) {
            $perm->update(['name' => 'create-sales']);
        }
    }
};
