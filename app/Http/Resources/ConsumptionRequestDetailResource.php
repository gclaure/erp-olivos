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

        $imageUrl = null;
        $categories = [];
        $images = [];
        if ($this->product) {
            $imageUrl = $this->product->image_path 
                ? (str_starts_with($this->product->image_path, 'http') ? $this->product->image_path : \Illuminate\Support\Facades\Storage::url($this->product->image_path))
                : (isset($this->product->drive_links[0]) ? $this->product->drive_links[0] : null);
                
            if ($this->product->relationLoaded('categories')) {
                $categories = $this->product->categories->pluck('name')->toArray();
            }

            // Configurar lista completa de imágenes del producto
            $driveLinks = is_array($this->product->drive_links) ? $this->product->drive_links : (json_decode($this->product->drive_links ?? '[]', true) ?? []);
            if ($imageUrl) {
                $images[] = $imageUrl;
            }
            foreach ($driveLinks as $link) {
                if ($link && !in_array($link, $images)) {
                    $images[] = $link;
                }
            }
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'product_code' => $this->product?->code,
            'product_image_url' => $imageUrl,
            'product_images' => $images,
            'product_description' => $this->product?->description,
            'product_location' => $this->product?->location,
            'product_brand' => $this->product?->brand,
            'product_categories' => $categories,
            'product_package_name' => $this->product?->package_name,
            'product_units_per_package' => (float) ($this->product?->units_per_package ?? 1),
            'product_has_expiration' => (bool) $this->product?->has_expiration,
            'unit_of_measure' => $this->product?->unitOfMeasure?->abbreviation ?? $this->product?->unitOfMeasure?->name ?? 'U',
            'quantity_requested' => (float) $this->quantity_requested,
            'quantity_delivered' => (float) $this->quantity_delivered,
            'quantity_received' => $this->quantity_received !== null ? (float) $this->quantity_received : null,
            'observation' => $this->observation,
            'stock_available' => $stock,
        ];
    }
}
