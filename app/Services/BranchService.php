<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BranchService
{
    private const SESSION_BRANCH_ID = 'active_branch_id';
    private const SESSION_BRANCH_NAME = 'active_branch_name';
    private const SESSION_WAREHOUSE_ID = 'active_warehouse_id';

    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    /**
     * Create a new branch with a default warehouse and point of sale.
     */
    public function createBranch(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['is_main']) && $data['is_main']) {
                $this->ensureSingleMainBranch((string)$data['company_id']);
            }

            $branch = Branch::create($data);

            // Create default Warehouse (this will automatically create a POS via WarehouseService)
            $this->warehouseService->create([
                'branch_id' => $branch->id,
                'name' => "Almacén Principal - {$branch->name}",
                'address' => $branch->address,
                'is_active' => true,
            ]);

            return $branch;
        });
    }

    /**
     * Update an existing branch and ensure only one is main if requested.
     */
    public function updateBranch(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            if (isset($data['is_main']) && $data['is_main']) {
                $this->ensureSingleMainBranch((string)$branch->company_id, $branch->id);
            }

            $branch->update($data);

            return $branch;
        });
    }

    /**
     * Ensure only one branch is marked as main for the company.
     */
    private function ensureSingleMainBranch(string $companyId, ?string $exceptId = null): void
    {
        $query = Branch::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('is_main', true);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_main' => false]);
    }

    /**
     * Get the active branch from session.
     */
    public function getActiveBranch(): ?Branch
    {
        $id = Session::get(self::SESSION_BRANCH_ID);
        return $id ? Branch::find($id) : null;
    }

    /**
     * Get the active branch ID.
     */
    public function getActiveBranchId(): ?string
    {
        return Session::get(self::SESSION_BRANCH_ID);
    }

    /**
     * Get the active branch name from session.
     */
    public function getActiveBranchName(): ?string
    {
        return Session::get(self::SESSION_BRANCH_NAME);
    }

    /**
     * Set the active branch in session.
     */
    public function setActiveBranch(string $branchId): void
    {
        $branch = Branch::find($branchId);
        
        Session::put(self::SESSION_BRANCH_ID, $branchId);
        Session::put(self::SESSION_BRANCH_NAME, $branch ? $branch->name : 'N/A');
        
        // Auto-select first warehouse if available
        $firstWarehouse = Warehouse::where('branch_id', $branchId)->first();
        if ($firstWarehouse) {
            Session::put(self::SESSION_WAREHOUSE_ID, (string) $firstWarehouse->id);
        } else {
            Session::forget(self::SESSION_WAREHOUSE_ID);
        }
    }

    /**
     * Get the active warehouse from session.
     */
    public function getActiveWarehouse(): ?Warehouse
    {
        $id = Session::get(self::SESSION_WAREHOUSE_ID);
        return $id ? Warehouse::find($id) : null;
    }

    /**
     * Get the active warehouse ID.
     */
    public function getActiveWarehouseId(): ?string
    {
        return Session::get(self::SESSION_WAREHOUSE_ID);
    }

    /**
     * Set the active warehouse in session.
     */
    public function setActiveWarehouse(string $warehouseId): void
    {
        Session::put(self::SESSION_WAREHOUSE_ID, $warehouseId);
    }

    /**
     * Clear all branch context from session.
     */
    public function clearContext(): void
    {
        Session::forget(self::SESSION_BRANCH_ID);
        Session::forget(self::SESSION_BRANCH_NAME);
        Session::forget(self::SESSION_WAREHOUSE_ID);
    }

    /**
     * Check if a branch can be deactivated.
     */
    public function canDeactivate(string $branchId): bool
    {
        // "No se puede desactivar una sucursal si tiene operaciones activas (ventas del día)."
        $today = now()->toDateString();
        
        return !\App\Models\Sale::withoutGlobalScope('branch_scoped')
            ->whereIn('warehouse_id', function ($query) use ($branchId) {
                $query->select('id')
                    ->from('warehouses')
                    ->where('branch_id', $branchId);
            })
            ->whereDate('date', $today)
            ->exists();
    }

    /**
     * Check if a branch is active in session.
     */
    public function hasActiveBranch(): bool
    {
        return Session::has(self::SESSION_BRANCH_ID);
    }
}
