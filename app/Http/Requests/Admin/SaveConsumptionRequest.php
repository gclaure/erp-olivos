<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveConsumptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'requested_by' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.id' => ['required', 'uuid', 'exists:products,id'],
            'cart.*.quantity' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'El almacén de origen es obligatorio.',
            'warehouse_id.exists' => 'El almacén seleccionado no es válido.',
            'requested_by.required' => 'El área solicitante (por ejemplo, Cocina) es obligatoria.',
            'cart.required' => 'Debe agregar al menos un producto a la solicitud.',
            'cart.min' => 'Debe agregar al menos un producto a la solicitud.',
            'cart.*.id.required' => 'El producto seleccionado no es válido.',
            'cart.*.id.exists' => 'El producto seleccionado no existe en el catálogo.',
            'cart.*.quantity.required' => 'La cantidad es obligatoria.',
            'cart.*.quantity.numeric' => 'La cantidad debe ser un valor numérico.',
            'cart.*.quantity.gt' => 'La cantidad debe ser mayor que cero.',
        ];
    }
}
