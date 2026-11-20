<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Order;

use App\Enums\OrderComplementProviderEnum;
use App\Enums\OrderDeliveryModeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'customer' => ['required', 'array'],
            'customer.id' => ['nullable', 'uuid', 'exists:customers,id'],
            'customer.full_name' => ['required', 'string', 'max:180'],
            'customer.phone' => ['required', 'string', 'max:40'],

            'registered_at' => ['nullable', 'date'],
            'delivery_date' => ['required', 'date'],
            'delivery_time' => ['required', 'string', 'max:30'],
            'delivery_mode' => ['required', Rule::enum(OrderDeliveryModeEnum::class)],
            'delivery_address' => [
                'nullable',
                'required_if:delivery_mode,delivery',
                'string',
                'max:500',
            ],

            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
            'general_notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:220'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.portions_or_size' => ['nullable', 'string', 'max:120'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.custom_description' => ['nullable', 'string', 'max:2000'],
            'items.*.reference_notes' => ['nullable', 'string', 'max:500'],
            'items.*.observations' => ['nullable', 'string', 'max:1000'],

            'items.*.details' => ['nullable', 'array'],
            'items.*.details.*.label' => ['nullable', 'string', 'max:120'],
            'items.*.details.*.value' => ['nullable', 'string', 'max:500'],

            'items.*.complements' => ['nullable', 'array'],
            'items.*.complements.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.complements.*.provider_type' => [
                'nullable',
                Rule::enum(OrderComplementProviderEnum::class),
            ],

            'items.*.images' => ['nullable', 'array'],
            'items.*.images.*' => ['file', 'image', 'mimes:webp,jpeg,jpg,png', 'max:10240'],

            'complements' => ['nullable', 'array'],
            'complements.*.description' => ['nullable', 'string', 'max:255'],
            'complements.*.provider_type' => [
                'nullable',
                Rule::enum(OrderComplementProviderEnum::class),
            ],

            'payments' => ['nullable', 'array'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.payment_method' => ['nullable', 'string', 'max:60'],
            'payments.*.paid_at' => ['nullable', 'date'],
            'payments.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer.full_name.required' => 'El nombre completo del cliente es obligatorio.',
            'customer.phone.required' => 'El número de celular del cliente es obligatorio.',
            'delivery_date.required' => 'La fecha programada de entrega es obligatoria.',
            'delivery_time.required' => 'La hora de entrega es obligatoria.',
            'delivery_mode.required' => 'Seleccione la modalidad de entrega.',
            'delivery_address.required_if' => 'La dirección es obligatoria cuando la modalidad es Entrega a domicilio.',
            'items.required' => 'Debe registrar al menos un producto en el pedido.',
            'items.*.product_name.required' => 'El nombre o tipo del producto es obligatorio.',
            'items.*.quantity.required' => 'La cantidad del producto es obligatoria.',
        ];
    }
}

