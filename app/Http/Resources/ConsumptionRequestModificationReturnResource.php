<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumptionRequestModificationReturnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'returned_quantity' => (float) $this->returned_quantity,
            'returned_at' => $this->returned_at?->format('Y-m-d H:i:s'),
            'returned_at_formatted' => $this->returned_at ? $this->returned_at->translatedFormat('d M Y, H:i') : '—',
            'return_notes' => $this->return_notes,
            'returned_by_user' => $this->relationLoaded('returnedByUser') && $this->returnedByUser ? [
                'id' => $this->returnedByUser->id,
                'name' => $this->returnedByUser->name,
            ] : null,
        ];
    }
}
