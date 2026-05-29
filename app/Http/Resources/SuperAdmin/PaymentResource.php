<?php

declare(strict_types=1);

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'tenant' => [
                'id' => $this->tenant?->id,
                'name' => $this->tenant?->name ?? '—',
            ],
            'amount' => (float) $this->amount,
            'amount_formatted' => 'Bs. ' . number_format((float) $this->amount, 2),
            'method' => $this->method?->value,
            'method_label' => $this->method?->label() ?? '—',
            'reference' => $this->reference,
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label() ?? 'N/A',
            'status_color' => $this->getStatusColor(),
            'paid_at' => $this->paid_at?->format('d/m/Y H:i') ?? '—',
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }

    private function getStatusColor(): string
    {
        return match($this->status) {
            \App\Enums\PaymentStatus::PAID => 'emerald',
            \App\Enums\PaymentStatus::PENDING => 'amber',
            \App\Enums\PaymentStatus::FAILED => 'red',
            default => 'zinc',
        };
    }
}
