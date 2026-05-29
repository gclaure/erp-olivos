<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\POS;

use App\Enums\DeliveryMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja mediante middleware de permisos en las rutas
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'operation_type' => ['required', Rule::in(['sale', 'quotation'])],
            'client_id' => ['required', 'exists:clients,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],

            
            // Carrito
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.id' => ['required', 'exists:products,id'],
            'cart.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'cart.*.price' => ['required', 'numeric', 'min:0'],
            'cart.*.discount' => ['nullable', 'numeric', 'min:0'],
            'cart.*.glosa' => ['nullable', 'string', 'max:250'],
            
            // Totales y Descuentos
            'global_discount' => ['nullable', 'numeric', 'min:0'],
            'is_fixed_discount' => ['required', 'boolean'],
            
            // Crédito
            'payment_type' => ['required_if:operation_type,sale', Rule::in(['efectivo', 'credito'])],
            'credit_days' => ['required_if:payment_type,credito', 'nullable', 'integer', 'min:1'],
            'due_date' => ['required_if:payment_type,credito', 'nullable', 'date'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            
            // Pago en Efectivo
            'payment_method' => ['required_if:payment_type,efectivo', 'nullable', Rule::in(['efectivo', 'tarjeta', 'qr'])],
            'amount_received' => ['required_if:payment_type,efectivo', 'nullable', 'numeric', 'min:0'],
            
            // Entrega
            'delivery_mode' => ['required', 'string'],
            'delivery_address' => ['required_if:delivery_mode,envio_domicilio', 'nullable', 'string', 'max:500'],
            'delivery_cost' => ['required_if:delivery_mode,envio_domicilio', 'nullable', 'numeric', 'min:0'],
            'delivery_contact_name' => ['nullable', 'string', 'max:255'],
            'delivery_contact_phone' => ['nullable', 'string', 'max:20'],
            'delivery_at' => ['nullable', 'date'],
            'delivery_observations' => ['nullable', 'string', 'max:1000'],
            
            // Encomienda
            'shipping_company' => ['required_if:delivery_mode,envio_encomienda', 'nullable', 'string', 'max:255'],
            'shipping_origin' => ['required_if:delivery_mode,envio_encomienda', 'nullable', 'string', 'max:255'],
            'shipping_destination' => ['required_if:delivery_mode,envio_encomienda', 'nullable', 'string', 'max:255'],
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
            'client_id' => 'cliente',
            'warehouse_id' => 'almacén',

            'cart' => 'carrito de compras',
            'global_discount' => 'descuento global',
            'payment_type' => 'condición de pago',
            'credit_days' => 'días de crédito',
            'due_date' => 'fecha de vencimiento',
            'delivery_mode' => 'modo de entrega',
            'delivery_address' => 'dirección de entrega',
            'delivery_cost' => 'costo de envío',
            'shipping_company' => 'empresa de transporte',
        ];
    }
}
