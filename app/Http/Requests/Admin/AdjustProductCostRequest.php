<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustProductCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador']);
    }

    public function rules(): array
    {
        return [
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_cost.required' => 'El costo unitario es obligatorio.',
            'unit_cost.numeric' => 'El costo unitario debe ser un valor numérico.',
            'unit_cost.min' => 'El costo unitario debe ser mayor o igual a cero.',
            'warehouse_id.exists' => 'El almacén seleccionado no es válido.',
        ];
    }
}
