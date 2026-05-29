<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumptionRequestDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $warehouseId = $this->consumptionRequest?->warehouse_id;
        $stock = 0.0;
        
        if ($warehouseId && $this->product) {
            $stock = (float) ($this->product->stocks->where('warehouse_id', $warehouseId)->first()?->quantity ?? 0);
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'product_code' => $this->product?->code,
            'unit_of_measure' => $this->product?->unitOfMeasure?->abbreviation ?? $this->product?->unitOfMeasure?->name ?? 'U',
            'quantity_requested' => (float) $this->quantity_requested,
            'quantity_delivered' => (float) $this->quantity_delivered,
            'quantity_received' => $this->quantity_received !== null ? (float) $this->quantity_received : null,
            'observation' => $this->observation,
            'stock_available' => $stock,
        ];
    }
}
