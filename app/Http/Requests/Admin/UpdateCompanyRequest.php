<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage-company');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nit' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'show_name' => ['required', 'boolean'],
            'inventory_method' => ['required', 'string', \Illuminate\Validation\Rule::in(['PROMEDIO_PONDERADO', 'PEPS'])],
            'inventories_closed_until' => ['nullable', 'date'],
        ];
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $company = app('tenant')->resolve();
            
            // Bloqueo total del método si ya hay movimientos
            if ($this->has('inventory_method') && $this->inventory_method !== $company->inventory_method->value) {
                if ($company->has_inventory_movements) {
                    $validator->errors()->add('inventory_method', 'No se puede cambiar el método de inventario una vez que ya existen movimientos registrados.');
                }
            }
        });
    }
}
