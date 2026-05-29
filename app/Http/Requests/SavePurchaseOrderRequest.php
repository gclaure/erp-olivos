<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavePurchaseOrderRequest extends FormRequest
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
            'provider_id' => ['nullable', 'uuid', 'exists:providers,id'],
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'details.*.quantity' => ['required', 'numeric', 'gt:0'],
            'details.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'from_consumption_id' => ['nullable', 'uuid', 'exists:consumption_requests,id'],
            'from_consumption_ids' => ['nullable', 'array'],
            'from_consumption_ids.*' => ['uuid', 'exists:consumption_requests,id'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'provider_id' => 'proveedor',
            'warehouse_id' => 'almacén',
            'date' => 'fecha',
            'notes' => 'notas',
            'details' => 'detalles de la solicitud',
            'details.*.product_id' => 'producto',
            'details.*.quantity' => 'cantidad',
            'details.*.unit_price' => 'precio unitario',
        ];
    }
}
