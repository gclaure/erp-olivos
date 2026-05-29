<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountReceivableResource extends JsonResource
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
            'client' => [
                'id' => $this->client?->id,
                'name' => $this->client?->name,
                'document_number' => $this->client?->document_number,
            ],
            'sale' => [
                'id' => $this->sale?->id,
                'number' => $this->sale?->number,
                'total' => (float) $this->sale?->total,
            ],
            'total_amount' => (float) $this->total_amount,
            'balance' => (float) $this->balance,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'status' => $this->status,
            'is_overdue' => $this->due_date?->isPast() && $this->status !== 'pagado',
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'payments' => AccountReceivablePaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
