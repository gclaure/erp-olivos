<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = \App\Models\Permission::firstOrCreate(['name' => 'manage-company']);
        
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = \App\Models\Permission::where('name', 'manage-company')->first();
        if ($permission) {
            $adminRole = \App\Models\Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->revokePermissionTo($permission);
            }
            $permission->delete();
        }
    }
};
