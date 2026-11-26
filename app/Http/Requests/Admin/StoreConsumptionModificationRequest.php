<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsumptionModificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('request-consumption-modification');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'to_product_id' => ['required', 'uuid', 'exists:products,id'],
            'requested_quantity' => ['required', 'numeric', 'gt:0'],
            'quantity_prepared' => ['nullable', 'numeric', 'min:0'],
            'quantity_physically_delivered' => ['nullable', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_product_id.required' => 'Debe seleccionar el producto solicitado.',
            'to_product_id.exists' => 'El producto seleccionado no es válido.',
            'requested_quantity.required' => 'La cantidad solicitada es obligatoria.',
            'requested_quantity.gt' => 'La cantidad solicitada debe ser mayor que cero.',
            'quantity_prepared.min' => 'La cantidad preparada en almacén no puede ser negativa.',
            'quantity_physically_delivered.min' => 'La cantidad entregada físicamente no puede ser negativa.',
            'reason.required' => 'El motivo de la modificación es obligatorio.',
            'reason.min' => 'El motivo debe contener al menos 3 caracteres.',
        ];
    }
}
