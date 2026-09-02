<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define Permissions
        $permissions = [
            'create-consumption' => 'Registrar Consumo',
            'manage-consumption' => 'Gestionar Consumos',
            'manage-inventory'   => 'Gestionar Inventario',
            'manage-products'    => 'Gestionar Productos',
            'manage-categories'  => 'Gestionar Categorías',
            'manage-warehouses'  => 'Gestionar Almacenes',
            'create-purchases'   => 'Crear Compras',
            'manage-purchases'   => 'Gestionar Compras',
            'manage-providers'   => 'Gestionar Proveedores',
            'manage-users'       => 'Gestionar Usuarios',
            'manage-roles'       => 'Gestionar Roles',
            'manage-company'     => 'Gestionar Datos de Empresa',
            'manage-branches'    => 'Administrar Sucursales',
            'view-reports'       => 'Ver Reportes BI',
        ];

        foreach ($permissions as $name => $label) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Define Roles
        
        // 1. Administrador (All permissions synced)
        $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all());
        
        // 2. Super Admin
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        // 3. Almacén (Asignar permisos correspondientes)
        $warehouseRole = Role::firstOrCreate(['name' => 'Almacén', 'guard_name' => 'web']);
        $warehouseRole->syncPermissions([
            'manage-products',
            'manage-categories',
            'manage-warehouses',
            'manage-inventory',
            'manage-consumption',
            'manage-purchases',
            'manage-providers',
        ]);

        // 4. Consumidor (Asignar permiso de solicitud de consumo)
        $consumerRole = Role::firstOrCreate(['name' => 'Consumidor', 'guard_name' => 'web']);
        $consumerRole->syncPermissions([
            'create-consumption',
        ]);
    }
}
