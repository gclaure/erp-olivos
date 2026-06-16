<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use App\Models\Company;
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

        $this->setupRolesForTenant();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo para producción, se puede dejar vacío
    }

    /**
     * Helper to setup roles and permissions for a specific tenant.
     */
    private function setupRolesForTenant(): void
    {
        // Crear permisos si no existen
        $inventoryPermission = Permission::firstOrCreate(['name' => 'manage-inventory', 'guard_name' => 'web']);
        $purchasesPermission = Permission::firstOrCreate(['name' => 'manage-purchases', 'guard_name' => 'web']);

        // 2. Almacén (Warehouse/Store Role)
        $warehouseRole = Role::firstOrCreate([
            'name' => 'Almacén',
            'guard_name' => 'web'
        ]);
        $warehouseRole->syncPermissions([
            $inventoryPermission,
            $purchasesPermission
        ]);
    }
};
