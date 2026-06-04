<?php

declare(strict_types=1);

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
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_uuid';

        // 1. model_has_roles
        if (Schema::hasColumn($tableNames['model_has_roles'], 'tenant_id')) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table): void {
                $table->dropPrimary('model_has_roles_pkey');
            });

            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($pivotRole, $modelMorphKey): void {
                $table->dropColumn('tenant_id');
                $table->primary([$pivotRole, $modelMorphKey, 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        // 2. model_has_permissions
        if (Schema::hasColumn($tableNames['model_has_permissions'], 'tenant_id')) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table): void {
                $table->dropPrimary('model_has_permissions_pkey');
            });

            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($pivotPermission, $modelMorphKey): void {
                $table->dropColumn('tenant_id');
                $table->primary([$pivotPermission, $modelMorphKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        // 3. roles
        if (Schema::hasColumn($tableNames['roles'], 'tenant_id')) {
            Schema::table($tableNames['roles'], function (Blueprint $table): void {
                $table->dropUnique('roles_tenant_id_name_guard_name_unique');
                $table->dropColumn('tenant_id');
                $table->unique(['name', 'guard_name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_uuid';

        // 1. roles
        if (!Schema::hasColumn($tableNames['roles'], 'tenant_id')) {
            Schema::table($tableNames['roles'], function (Blueprint $table): void {
                $table->uuid('tenant_id')->nullable();
                $table->dropUnique(['name', 'guard_name']);
                $table->unique(['tenant_id', 'name', 'guard_name'], 'roles_tenant_id_name_guard_name_unique');
            });
        }

        // 2. model_has_permissions
        if (!Schema::hasColumn($tableNames['model_has_permissions'], 'tenant_id')) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table): void {
                $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            });

            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($pivotPermission, $modelMorphKey): void {
                $table->uuid('tenant_id')->default('00000000-0000-0000-0000-000000000000');
                $table->primary(['tenant_id', $pivotPermission, $modelMorphKey, 'model_type'], 'model_has_permissions_pkey');
            });
        }

        // 3. model_has_roles
        if (!Schema::hasColumn($tableNames['model_has_roles'], 'tenant_id')) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table): void {
                $table->dropPrimary('model_has_roles_role_model_type_primary');
            });

            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($pivotRole, $modelMorphKey): void {
                $table->uuid('tenant_id')->default('00000000-0000-0000-0000-000000000000');
                $table->primary(['tenant_id', $pivotRole, $modelMorphKey, 'model_type'], 'model_has_roles_pkey');
            });
        }
    }
};
