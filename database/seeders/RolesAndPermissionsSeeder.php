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
        
        // 1. Administrador (All permissions)
        $adminRole = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        // Note: Admin gets all permissions via Gate::before in AppServiceProvider
        
        // 2. Vendedor (Seller)
        $sellerRole = Role::firstOrCreate(['name' => 'Vendedor', 'guard_name' => 'web']);
        $sellerRole->syncPermissions([
            'pos-access',
            'manage-sales',
            'manage-products',
            'manage-clients',
        ]);
        
        // 3. Super Admin
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    }
}
