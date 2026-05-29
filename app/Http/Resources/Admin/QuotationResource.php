<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class QuotationResource extends JsonResource
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
            'date' => $this->date,
            'formatted_date' => $this->date ? Carbon::parse($this->date)->translatedFormat('l, d \d\e F \d\e Y') : '—',
            'client' => [
                'id' => $this->client->id ?? null,
                'name' => $this->client->name ?? '—',
            ],
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? '—',
            ],
            'status' => $this->status,
            'status_label' => match($this->status) {
                'cancelada' => 'Cancelada',
                'completada' => 'Completada',
                default => 'Pendiente',
            },
            'total' => (float)($this->total ?? 0),
            'items_count' => (int)($this->items_count ?? 0),
        ];
    }
}
