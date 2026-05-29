<?php

declare(strict_types=1);

namespace App\Traits;

use App\Facades\Branch;
use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    /**
     * Boot the trait.
     */
    protected static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch_scoped', function (Builder $builder) {
            if (Branch::hasActiveBranch()) {
                $model = $builder->getModel();
                $table = $model->getTable();
                $branchId = Branch::getActiveBranchId();

                // If the model has a custom scoping logic, use it
                if (method_exists($model, 'scopeByBranch')) {
                    $model->scopeByBranch($builder, $branchId);
                    return;
                }

                // Default: check for branch_id column
                // We'll trust the developer to apply it to models that have this column or warehouse_id
                
                // Note: For production it's better to avoid Schema::hasColumn in global scopes
                // We will assume 'branch_id' or 'warehouse_id' exists if the trait is used, 
                // or the model overrides scopeByBranch.
                
                if (isset($model->branch_column)) {
                    $builder->where($table . '.' . $model->branch_column, $branchId);
                } elseif ($table === 'warehouses') {
                    $builder->where($table . '.branch_id', $branchId);
                } elseif ($table === 'branches') {
                    $builder->where($table . '.id', $branchId);
                } else {
                    // Default fallback for operational models using warehouse_id
                    $builder->whereIn($table . '.warehouse_id', function ($query) use ($branchId) {
                        $query->select('id')
                            ->from('warehouses')
                            ->where('branch_id', $branchId);
                    });
                }
            }
        });
    }
}
