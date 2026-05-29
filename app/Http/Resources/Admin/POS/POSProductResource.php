<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\POS;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class POSProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'price' => (float) $this->price,
            'image_path' => $this->image_path 
                ? (str_starts_with($this->image_path, 'http') ? $this->image_path : \Illuminate\Support\Facades\Storage::url($this->image_path))
                : (isset($this->drive_links[0]) ? $this->drive_links[0] : null),
            'unit_cost' => (float) ($this->unit_cost ?? 0),
            'max_stock' => $this->stocks->sum('quantity'),
            'reserved_quantity' => $this->reserved_quantity ?? 0,
            'unit' => $this->unitOfMeasure?->abbreviation,
            'unit_name' => $this->unitOfMeasure?->name,
            'units_per_package' => (float) ($this->units_per_package ?? 0),
            'package_name' => $this->package_name,
            'location' => $this->location,
            'brand' => $this->brand,
            'has_expiration' => (bool) $this->has_expiration,
            'expiration_date' => $this->nearest_expiration_date ? $this->nearest_expiration_date : null,
            'categories' => $this->categories->pluck('name')->toArray(),
            'stocks' => $this->stocks->map(fn($stock) => [
                'warehouse_id' => $stock->warehouse_id,
                'quantity' => $stock->quantity,
            ]),
        ];
    }
}
