<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class SaleResource extends JsonResource
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
            'number' => $this->number,
            'formatted_number' => str_pad((string)($this->number ?? 0), 6, '0', STR_PAD_LEFT),
            'date' => $this->date,
            'formatted_date' => $this->date ? Carbon::parse($this->date)->translatedFormat('l, d \d\e F \d\e Y') : '—',
            'client' => [
                'id' => $this->client->id ?? null,
                'name' => $this->client->name ?? '—',
                'document_type' => $this->client->document_type ?? null,
                'document_number' => $this->client->document_number ?? null,
            ],
            'user' => [
                'id' => $this->user->id ?? null,
                'name' => $this->user->name ?? '—',
            ],
            'warehouse' => [
                'id' => $this->warehouse->id ?? null,
                'name' => $this->warehouse->name ?? '—',
            ],
            'point_of_sale' => [
                'id' => null,
                'name' => '—',
            ],
            'subtotal' => (float)($this->subtotal ?? 0),
            'global_discount' => (float)($this->global_discount ?? 0),
            'total' => (float)($this->total ?? 0),
            'total_payment' => (float)($this->total_payment ?? 0),
            'pending_balance' => (float)($this->total ?? 0) - (float)($this->total_payment ?? 0),
            'status' => $this->status,
            'status_label' => ucfirst($this->status ?? 'completada'),
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'reason_cancel' => $this->reason_cancel,
            'details' => $this->whenLoaded('details', function() {
                return $this->details->map(fn($detail) => [
                    'id' => $detail->id,
                    'product_name' => $detail->product->name ?? '—',
                    'product_code' => $detail->product->code ?? '—',
                    'quantity' => (float)$detail->quantity,
                    'price' => (float)$detail->price,
                    'discount' => (float)$detail->discount,
                    'subtotal' => (float)$detail->subtotal,
                ]);
            }),
        ];
    }
}
