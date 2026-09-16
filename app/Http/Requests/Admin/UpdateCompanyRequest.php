<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Facades\CompanyFacade;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'show_name' => ['required', 'boolean'],
            'receipt_type' => ['required', 'string', Rule::in(['media', 'rollo'])],
            'inventory_method' => ['sometimes', 'nullable', 'string', Rule::in(['PROMEDIO_PONDERADO', 'PEPS'])],
            'inventories_closed_until' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $company = CompanyFacade::getCompany();

            if (!$company) {
                return;
            }

            $currentMethod = is_object($company->inventory_method) && isset($company->inventory_method->value)
                ? $company->inventory_method->value
                : (string) $company->inventory_method;

            // Bloqueo total del método si ya hay movimientos y se envía un cambio explícito
            if ($this->filled('inventory_method') && $this->input('inventory_method') !== $currentMethod) {
                if ($company->has_inventory_movements) {
                    $validator->errors()->add('inventory_method', 'No se puede cambiar el método de inventario una vez que ya existen movimientos registrados.');
                }
            }
        });
    }
}
