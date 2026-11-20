<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:60'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'El monto del abono es obligatorio.',
            'amount.min' => 'El monto del abono debe ser mayor a 0.',
            'payment_method.required' => 'Seleccione o ingrese el método de pago.',
        ];
    }
}

