<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountPayableResource extends JsonResource
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
            'purchase' => [
                'id' => $this->purchase?->id,
                'purchase_number' => $this->purchase?->purchase_number,
                'date' => $this->purchase?->date?->format('Y-m-d'),
                'date_formatted' => $this->purchase?->date?->translatedFormat('l, d \d\e F \d\e Y'),
            ],
            'provider' => [
                'id' => $this->provider?->id,
                'name' => $this->provider?->name,
            ],
            'total_amount' => (float) $this->total_amount,
            'paid_amount' => (float) $this->paid_amount,
            'balance' => (float) $this->balance,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_date_formatted' => $this->due_date?->translatedFormat('l, d \d\e F \d\e Y'),
            'is_overdue' => $this->due_date?->isPast() && $this->status !== 'PAID',
            'status' => $this->status,
            'payments' => AccountPayablePaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
