<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AccountPayablePaymentResource extends JsonResource
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
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'payment_date_formatted' => $this->payment_date?->translatedFormat('l, d \d\e F \d\e Y'),
            'payment_method' => $this->payment_method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'receipt_url' => $this->receipt_path ? Storage::url($this->receipt_path) : null,
            'user_name' => $this->user?->name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
