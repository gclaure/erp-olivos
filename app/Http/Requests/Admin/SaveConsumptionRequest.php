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
            // warehouse_id en raíz es opcional: cada ítem trae su propio warehouse_id
            'warehouse_id'            => ['nullable', 'uuid', 'exists:warehouses,id'],
            'requested_by'            => ['nullable', 'string', 'in:Cocina,Pastelería,Eventos', 'max:100'],
            'notes'                   => ['nullable', 'string'],
            'cart'                    => ['required', 'array', 'min:1'],
            'cart.*.id'               => ['required', 'uuid', 'exists:products,id'],
            'cart.*.quantity'         => ['required', 'numeric', 'gt:0'],
            'cart.*.warehouse_id'     => ['required', 'uuid', 'exists:warehouses,id'],
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
            'warehouse_id.exists'           => 'El almacén seleccionado no es válido.',
            'requested_by.required'         => 'El área solicitante (por ejemplo, Cocina) es obligatoria.',
            'cart.required'                 => 'Debe agregar al menos un producto a la solicitud.',
            'cart.min'                      => 'Debe agregar al menos un producto a la solicitud.',
            'cart.*.id.required'            => 'El producto seleccionado no es válido.',
            'cart.*.id.exists'              => 'El producto seleccionado no existe en el catálogo.',
            'cart.*.quantity.required'      => 'La cantidad es obligatoria.',
            'cart.*.quantity.numeric'       => 'La cantidad debe ser un valor numérico.',
            'cart.*.quantity.gt'            => 'La cantidad debe ser mayor que cero.',
            'cart.*.warehouse_id.required'  => 'Cada producto debe tener un almacén de origen.',
            'cart.*.warehouse_id.exists'    => 'El almacén de origen de un producto no es válido.',
        ];
    }

    /**
     * Configure the validator instance for custom rules.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $cart = $this->input('cart');
            if (!is_array($cart)) {
                return;
            }

            foreach ($cart as $index => $item) {
                if (!isset($item['id'], $item['warehouse_id'], $item['quantity'])) {
                    continue;
                }

                $productId = (string) $item['id'];
                $warehouseId = (string) $item['warehouse_id'];
                $requestedQty = (float) $item['quantity'];

                $stock = \App\Models\Stock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                $physicalStock = $stock ? (float) $stock->quantity : 0.0;

                // Calcular reservas de ventas (ventas activas y no entregadas)
                $salesReserved = (float) \App\Models\SaleDetail::query()
                    ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
                    ->where('sale_details.product_id', $productId)
                    ->where('sales.warehouse_id', $warehouseId)
                    ->where('sales.is_delivered', false)
                    ->where('sales.is_active', true)
                    ->sum('sale_details.quantity');

                // Calcular reservas de consumo (solicitudes de consumo pendientes/parciales de despachar)
                $consumptionReserved = (float) \App\Models\ConsumptionRequestDetail::query()
                    ->join('consumption_requests', 'consumption_requests.id', '=', 'consumption_request_details.consumption_request_id')
                    ->where('consumption_request_details.product_id', $productId)
                    ->where('consumption_requests.warehouse_id', $warehouseId)
                    ->whereIn('consumption_requests.status', ['pendiente', 'parcial', 'despachado_parcial', 'compras_generado'])
                    ->sum(\DB::raw('consumption_request_details.quantity_requested - consumption_request_details.quantity_delivered'));

                $reservedQty = $salesReserved + $consumptionReserved;
                $availableQty = max(0.0, $physicalStock - $reservedQty);

                if ($requestedQty > $availableQty) {
                    $productName = \App\Models\Product::where('id', $productId)->value('name') ?? 'Insumo';
                    $warehouseName = \App\Models\Warehouse::where('id', $warehouseId)->value('name') ?? 'Almacén';

                    $validator->errors()->add(
                        "cart.{$index}.quantity",
                        "No es posible solicitar el producto '{$productName}' en el almacén '{$warehouseName}'. Stock disponible: {$availableQty}, Solicitado: {$requestedQty}."
                    );
                }
            }
        });
    }
}
