<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Warehouse;

class WarehouseService
{
    /**
     * Create a new warehouse and its default point of sale.
     */
    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    /**
     * Check if a warehouse can be deactivated.
     */
    public function canDeactivate(string $warehouseId): bool
    {
        $warehouse = Warehouse::findOrFail($warehouseId);

        // Rule: "Si tiene stock registrado." (Current logic in WarehouseIndex)
        // We keep it as a safety measure.
        if ($warehouse->stocks()->where('quantity', '>', 0)->exists()) {
            return false;
        }

        return true;
    }
}
