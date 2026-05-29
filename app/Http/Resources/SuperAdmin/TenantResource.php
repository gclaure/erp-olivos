<?php

declare(strict_types=1);

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nit' => $this->nit,
            'business_name' => $this->business_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'phone_secondary' => $this->phone_secondary,
            'address' => $this->branches->where('is_main', true)->first()?->address ?? '',
            'vendedor_id' => $this->vendedor_id,
            'plan_id' => $this->activeSubscription?->plan_id ?? $this->subscriptions()->latest()->value('plan_id'),
            'trial_months' => (int) max(1, ceil($this->activeSubscription?->starts_at && $this->activeSubscription?->ends_at 
                ? $this->activeSubscription->starts_at->diffInDays($this->activeSubscription->ends_at) / 30 
                : 1)),
            'status' => $this->status->value ?? $this->status,
            'status_label' => $this->status->label() ?? $this->status,
            'status_color' => $this->status->color() ?? 'gray',
            'owner' => [
                'name' => $this->owner?->name,
                'email' => $this->owner?->email,
                'initials' => $this->owner?->initials,
            ],
            'vendedor_name' => $this->vendedor?->name ?? 'Sin asesor',
            'subscription' => $this->activeSubscription ? [
                'plan_name' => $this->activeSubscription->plan?->name ?? 'Personalizado',
                'ends_at' => $this->activeSubscription->ends_at?->format('d/m/Y') ?? 'Sin fecha',
            ] : null,
            'metrics' => [
                'branches_count' => $this->branches_count ?? 0,
                'users_count' => $this->users_count ?? 0,
                'branches_limit' => $this->activeSubscription?->plan?->branches_limit ?? '∞',
                'users_limit' => $this->activeSubscription?->plan?->users_limit ?? '∞',
            ],
            'roles' => $this->roles->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]),
        ];
    }
}
