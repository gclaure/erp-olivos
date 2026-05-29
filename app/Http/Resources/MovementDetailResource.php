<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementDetailResource extends JsonResource
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
            'type' => $this->type,
            'date' => $this->date ? $this->date->translatedFormat('l, d \d\e F \d\e Y') : $this->created_at->translatedFormat('l, d \d\e F \d\e Y'),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'warehouse' => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ],
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'details' => $this->details->map(fn($detail) => [
                'id' => $detail->id,
                'quantity' => (float) $detail->quantity,
                'product' => [
                    'id' => $detail->product?->id,
                    'name' => $detail->product?->name,
                    'code' => $detail->product?->code,
                ],
            ]),
        ];
    }
}
