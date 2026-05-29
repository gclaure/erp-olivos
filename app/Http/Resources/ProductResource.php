<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'warehouse_ids' => $this->whenLoaded('stocks', fn () => 
                $this->stocks->pluck('warehouse_id')->unique()->values()->all()
            ),
            'description' => $this->description,
            'image_path' => $this->image_path 
                ? (str_starts_with($this->image_path, 'http') ? $this->image_path : \Illuminate\Support\Facades\Storage::url($this->image_path))
                : (isset($this->drive_links[0]) ? $this->drive_links[0] : null),
            'price' => (float) $this->price,
            'min_stock' => (float) $this->min_stock,
            'is_active'          => (bool) $this->is_active,
            'has_expiration'     => (bool) $this->has_expiration,
            'show_in_ecommerce'  => (bool) $this->show_in_ecommerce,
            'drive_links' => is_array($this->drive_links) ? $this->drive_links : (json_decode($this->drive_links ?? '[]', true) ?? []),
            'unit_of_measure' => $this->whenLoaded('unitOfMeasure', fn () => [
                'id' => $this->unitOfMeasure->id,
                'name' => $this->unitOfMeasure->name,
                'abbreviation' => $this->unitOfMeasure->abbreviation,
            ]),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'current_stock' => (float) ($this->current_stock ?? 0),
            'location' => $this->location,
            'brand' => $this->brand,
            'slug' => $this->slug,
            'units_per_package' => (float) ($this->units_per_package ?? 1),
            'package_name' => $this->package_name,
            'warehouses' => $this->whenLoaded('stocks', fn () => 
                $this->stocks->map(fn($s) => $s->warehouse->name ?? null)->filter()->unique()->values()->all()
            ),
            'category_ids' => $this->whenLoaded('categories', fn() => $this->categories->pluck('id')),
        ];
    }
}
