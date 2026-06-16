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
            'manage-users' => 'Gestionar Usuarios',
            'manage-roles' => 'Gestionar Roles',
            'manage-branches' => 'Administrar sucursales',
            'manage-warehouses' => 'Gestionar Almacenes',
            'manage-pos' => 'Gestionar Puntos de Venta',
            'manage-products' => 'Gestionar Productos',
            'manage-categories' => 'Gestionar Categorías',
            'manage-inventory' => 'Gestionar Inventario',
            'manage-purchases' => 'Gestionar Compras',
            'manage-sales' => 'Gestionar Ventas',
            'pos-access' => 'Acceso al POS',
            'view-reports' => 'Ver Reportes',
            'manage-settings' => 'Gestionar Configuración',
            'manage-company' => 'Gestionar Datos de Empresa',
            'manage-transfers' => 'Gestionar Transferencias',
            'manage-providers' => 'Gestionar Proveedores',
            'manage-clients' => 'Gestionar Clientes',
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
            'manage-inventory',
            'manage-categories',
            'manage-warehouses',
            'manage-providers',
            'manage-purchases',
        ]);
    }
}
