<?php

declare(strict_types=1);

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
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
            'plan' => [
                'id' => $this->plan?->id,
                'name' => $this->plan?->name ?? '—',
                'price' => (float) ($this->plan?->price ?? 0),
                'billing_period' => $this->plan?->billing_period,
            ],
            'next_plan' => $this->nextPlan ? [
                'id' => $this->nextPlan->id,
                'name' => $this->nextPlan->name,
            ] : null,
            'starts_at' => $this->starts_at?->format('d/m/Y'),
            'ends_at' => $this->ends_at?->format('d/m/Y') ?? '∞',
            'billing_anchor_day' => $this->billing_anchor_day ?? $this->starts_at?->day ?? '—',
            'status' => $this->status?->value,
            'status_label' => $this->status?->label() ?? 'N/A',
            'status_color' => $this->getStatusColor(),
        ];
    }

    private function getStatusColor(): string
    {
        return match($this->status) {
            \App\Enums\SubscriptionStatus::ACTIVE => 'emerald',
            \App\Enums\SubscriptionStatus::EXPIRED => 'red',
            \App\Enums\SubscriptionStatus::CANCELLED => 'zinc',
            default => 'zinc',
        };
    }
}
