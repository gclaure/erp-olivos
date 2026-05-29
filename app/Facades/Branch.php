<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Branch|null getActiveBranch()
 * @method static string|null getActiveBranchId()
 * @method static string|null getActiveBranchName()
 * @method static void setActiveBranch(string $branchId)
 * @method static \App\Models\Warehouse|null getActiveWarehouse()
 * @method static string|null getActiveWarehouseId()
 * @method static void setActiveWarehouse(string $warehouseId)
 * @method static void clearContext()
 * @method static bool hasActiveBranch()
 * @method static bool canDeactivate(string $branchId)
 *
 * @see \App\Services\BranchService
 */
class Branch extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'branch';
    }
}
