<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
            'date' => $this->date?->format('Y-m-d'),
            'date_formatted' => $this->date ? $this->date->translatedFormat('l, d \d\e F \d\e Y') : '—',
            'provider' => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
            ],
            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'status' => $this->status, // 'pendiente', 'completada', 'cancelada'
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'details' => PurchaseOrderDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
