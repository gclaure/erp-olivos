<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
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
            'purchase_number' => $this->purchase_number,
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
            'status' => $this->status,
            'payment_type' => $this->payment_type,
            'voucher_type' => $this->voucher_type,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'cancellation_reason' => $this->cancellation_reason,
            'balance' => $this->payment_type === 'credito' && $this->accountPayable ? (float) $this->accountPayable->balance : null,
            'account_payable_id' => $this->accountPayable?->id,
            'account_payable_status' => $this->accountPayable?->status,
            'details' => PurchaseDetailResource::collection($this->whenLoaded('details')),
        ];
    }
}
