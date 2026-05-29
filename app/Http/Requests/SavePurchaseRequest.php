<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class SavePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id'    => ['required', 'exists:providers,id'],
            'warehouse_id'   => ['required', 'exists:warehouses,id'],
            'date'           => ['required', 'date'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'voucher_type'   => ['required', 'string', 'in:factura,sin_factura'],
            'payment_type'   => ['required', 'string', 'in:contado,credito'],
            'due_date'       => ['nullable', 'required_if:payment_type,credito', 'date', 'after_or_equal:date'],
            'down_payment'   => ['nullable', 'numeric', 'min:0'],
            'details'        => ['required', 'array', 'min:1'],
            'details.*.product_id'      => ['required', 'exists:products,id'],
            'details.*.quantity'        => ['required', 'numeric', 'min:0.01'],
            'details.*.unit_price'      => ['required', 'numeric', 'min:0.01'],
            // nullable para que false/0/"0" no fallen el required
            'details.*.has_expiration'  => ['nullable', 'boolean'],
            'details.*.expiration_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Validación adicional: expiration_date es obligatoria cuando has_expiration es true.
     * Se hace aquí porque required_if con wildcards (* ) no funciona correctamente en Laravel.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $details = $this->input('details', []);

            foreach ($details as $index => $detail) {
                $hasExpiration = filter_var($detail['has_expiration'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($hasExpiration && empty($detail['expiration_date'])) {
                    $v->errors()->add(
                        "details.{$index}.expiration_date",
                        'La fecha de vencimiento es obligatoria para este producto.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'provider_id.required'      => 'El proveedor es obligatorio.',
            'warehouse_id.required'     => 'El almacén de destino es obligatorio.',
            'details.required'          => 'Debe agregar al menos un producto a la compra.',
            'details.*.quantity.min'    => 'La cantidad debe ser mayor a cero.',
            'details.*.unit_price.min'  => 'El costo unitario debe ser mayor a cero.',
        ];
    }
}
