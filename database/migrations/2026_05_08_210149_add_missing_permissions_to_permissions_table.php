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
        $permissions = [
            'manage-transfers',
            'manage-deliveries',
            'manage-pos',
        ];

        foreach ($permissions as $permission) {
            \App\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'manage-transfers',
            'manage-deliveries',
            'manage-pos',
        ];

        \App\Models\Permission::whereIn('name', $permissions)->delete();
    }
};
