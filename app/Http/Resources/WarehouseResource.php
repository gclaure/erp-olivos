<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'branch' => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ],
            'address' => $this->address,
            'is_active' => $this->is_active,
            'cajas' => [],
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
