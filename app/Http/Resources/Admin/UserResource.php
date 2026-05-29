<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'initials' => $this->initials,
            'is_super_admin' => (bool) $this->is_super_admin,
            'branch_id' => $this->branch_id,
            'branch' => [
                'id' => $this->branch->id ?? null,
                'name' => $this->branch->name ?? 'N/A',
            ],
            'roles' => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
            ]),
            'point_of_sales' => [],
        ];
    }
}
