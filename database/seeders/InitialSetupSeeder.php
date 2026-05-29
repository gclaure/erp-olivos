<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Provider;
use App\Enums\TenantStatus;
use App\Enums\InventoryMethod;

class InitialSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define all permissions

        // 3. Define all permissions
        $permissionNames = [
            'create-purchases',
            'create-sales',
            'manage-categories',
            'manage-clients',
            'manage-inventory',
            'manage-products',
            'manage-providers',
            'manage-purchases',
            'manage-roles',
            'manage-sales',
            'manage-settings',
            'manage-users',
            'manage-warehouses',
            'manage-branches',
            'view-reports',
            'pos-access',
            'manage-pos',
            'manage-transfers',
            'manage-deliveries',
            'manage-company',
        ];

        foreach ($permissionNames as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // 4. Create Roles with Global Tenant ID
        $globalId = '00000000-0000-0000-0000-000000000000';
        setPermissionsTeamId($globalId);

        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Administrador',
            'guard_name' => 'web',
        ]);

        $managerRole = Role::firstOrCreate([
            'name' => 'Gerente',
            'guard_name' => 'web',
        ]);

        $sellerRole = Role::firstOrCreate([
            'name' => 'Vendedor',
            'guard_name' => 'web',
        ]);

        $managerPermissions = [
            'manage-products', 'manage-clients',
            'manage-providers', 'manage-purchases', 'manage-sales',
            'manage-inventory', 'view-reports', 'create-sales', 'create-purchases',
        ];
        $managerRole->syncPermissions($managerPermissions);

        $sellerPermissions = ['create-sales', 'pos-access'];
        $sellerRole->syncPermissions($sellerPermissions);

        // 6. Create Super Admin User
        $superAdminUser = User::firstOrCreate(
            ['email' => 'gclaure@gmail.com'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('admin123'),
                'is_super_admin' => true,
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'branch_id' => null,
            ]
        );
        
        // Asegurar que Spatie use el equipo global para el Super Admin
        setPermissionsTeamId($globalId);
        $superAdminUser->assignRole($superAdminRole);

        // 7. Default Company
        $company = Company::firstOrCreate(
            ['nit' => '00000000'],
            [
                'name' => 'Empresa Principal',
                'business_name' => 'Empresa Principal SRL',
                'phone' => '00000000',
                'email' => 'admin@empresa.com',
                'status' => TenantStatus::ACTIVE,
                'inventory_method' => InventoryMethod::FIFO,
            ]
        );

        // 8. Default Branch
        $branch = Branch::firstOrCreate(
            ['name' => 'Sucursal Principal'],
            [
                'company_id' => $company->id,
                'address' => 'Dirección Principal',
                'phone' => '00000000',
                'is_main' => true,
                'is_active' => true,
            ]
        );

        // Update Super Admin
        $superAdminUser->update(['branch_id' => $branch->id]);

        // 9. Default Warehouse
        $warehouse = Warehouse::firstOrCreate(
            ['name' => 'Almacén Principal'],
            [
                'branch_id' => $branch->id,
                'address' => 'Dirección Almacén',
                'is_active' => true,
            ]
        );
        
        $warehouse->users()->syncWithoutDetaching([$superAdminUser->id]);

        // 10. Default Provider
        Provider::firstOrCreate(
            ['document_number' => '0000000'],
            [
                'document_type' => 'CI',
                'name' => 'Proveedor General',
                'contact_name' => 'Contacto General',
                'email' => 'proveedor@general.com',
                'phone' => '00000000',
                'is_active' => true,
            ]
        );
    }
}
