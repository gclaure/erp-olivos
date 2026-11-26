<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $permission = Permission::firstOrCreate([
                'name' => 'manage-orders',
                'guard_name' => 'web',
            ]);

            $bodasRole = Role::firstOrCreate([
                'name' => 'Bodas',
                'guard_name' => 'web',
            ]);

            if (!$bodasRole->hasPermissionTo($permission)) {
                $bodasRole->givePermissionTo($permission);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            $bodasRole = Role::where('name', 'Bodas')->where('guard_name', 'web')->first();
            if ($bodasRole) {
                $bodasRole->delete();
            }
        }
    }
};
