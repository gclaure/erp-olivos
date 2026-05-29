<?php

declare(strict_types=1);

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'price_formatted' => 'Bs. ' . number_format((float) $this->price, 2),
            'duration_days' => $this->duration_days,
            'billing_period' => $this->billing_period,
            'is_active' => (bool) $this->is_active,
            'features' => $this->features ?? [
                'max_users' => 0,
                'max_branches' => 0,
                'max_products' => 0,
                'max_warehouses' => 0,
                'max_pos' => 0,
                'max_images_per_product' => 1,
                'has_catalog' => false,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
