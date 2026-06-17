<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KardexMovementType;
use App\Models\ConsumptionRequest;
use App\Models\Stock;
use App\Models\Kardex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ConsumptionRequestDispatchService
{
    public function __construct(private KardexService $kardexService) {}

    /**
     * Despacha el stock físico disponible para una solicitud de consumo.
     */
    public function dispatch(
        ConsumptionRequest $consumptionRequest,
        array $dispatchQuantities = [],
        array $observations = []
    ): ConsumptionRequest {
        if (!in_array($consumptionRequest->status, ['pendiente', 'aprobado', 'despachado_parcial'], true)) {
            throw new Exception("Solo se pueden despachar solicitudes en estado pendiente o con despacho parcial.");
        }

        return DB::transaction(function () use ($consumptionRequest, $dispatchQuantities, $observations) {
            $consumptionRequest->loadMissing('details.product');
            $warehouseId = $consumptionRequest->warehouse_id;
            $allDispatchedCompletely = true;
            $missingItems = [];

            foreach ($consumptionRequest->details as $detail) {
                $productId = $detail->product_id;
                $requestedQty = (float) $detail->quantity_requested;
                $alreadyDelivered = (float) $detail->quantity_delivered;
                $pendingQty = $requestedQty - $alreadyDelivered;

                if ($pendingQty <= 0) {
                    continue; // Ya entregado completamente
                }

                // Obtener stock disponible en el almacén
                $stock = Stock::withoutGlobalScopes()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->first();

                $availableStock = $stock ? (float) $stock->quantity : 0.0;

                // Calcular cantidad a entregar en esta ronda
                if (isset($dispatchQuantities[$detail->id])) {
                    $dispatchQty = (float) $dispatchQuantities[$detail->id];
                } else {
                    $dispatchQty = min($pendingQty, $availableStock);
                }

                if ($dispatchQty < 0) {
                    throw new Exception("La cantidad a despachar para '{$detail->product?->name}' no puede ser negativa.");
                }

                // Si la cantidad a despachar supera lo pendiente o lo disponible en almacén, requiere observación
                $exceedsPending = $dispatchQty > $pendingQty;
                $exceedsStock = $dispatchQty > $availableStock;

                if ($exceedsPending || $exceedsStock) {
                    $obs = $observations[$detail->id] ?? null;
                    if (empty(trim((string)$obs)) || strlen(trim((string)$obs)) < 3) {
                        $msg = $exceedsStock 
                            ? "Debe ingresar una observación/motivo de al menos 3 caracteres justificando el despacho para '{$detail->product?->name}' porque supera el stock disponible ({$availableStock})."
                            : "Debe ingresar una observación/motivo de al menos 3 caracteres justificando el despacho para '{$detail->product?->name}' porque supera la cantidad pendiente ({$pendingQty}).";
                        throw new Exception($msg);
                    }
                    $detail->observation = trim((string)$obs);
                } else {
                    // Si viene una observación opcional, la guardamos
                    $obs = $observations[$detail->id] ?? null;
                    if (!empty($obs)) {
                        $detail->observation = trim((string)$obs);
                    }
                }

                if ($dispatchQty > 0) {
                    // Obtener costo promedio del producto en este almacén
                    $lastKardex = Kardex::withoutGlobalScopes()
                        ->where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->latest('id')
                        ->first();
                    $avgCost = $lastKardex ? (string) $lastKardex->avg_cost : '0.0000';

                    // Registrar en Kardex como ADJUSTMENT_OUT (Salida de Consumo)
                    $this->kardexService->record(
                        type: KardexMovementType::ADJUSTMENT_OUT,
                        productId: $productId,
                        warehouseId: $warehouseId,
                        quantity: $dispatchQty,
                        unitCost: $avgCost,
                        userId: Auth::id(),
                        notes: "Despacho de Consumo Interno: {$consumptionRequest->requested_by}",
                        recordableType: ConsumptionRequest::class,
                        recordableId: $consumptionRequest->id
                    );

                    // Actualizar el detalle
                    $detail->quantity_delivered = $alreadyDelivered + $dispatchQty;
                    $detail->save();
                }

                // Calcular el faltante real
                $newDelivered = $detail->quantity_delivered;
                $missingQty = $requestedQty - $newDelivered;

                if ($missingQty > 0) {
                    $allDispatchedCompletely = false;
                    
                    // Obtener precio estimado (costo promedio o precio del producto)
                    $price = (float) ($detail->product->kardexes()->latest()->first()?->unit_cost ?? $detail->product->price ?? 0.0);
                    $subtotal = $missingQty * $price;

                    $missingItems[] = [
                        'product_id' => $productId,
                        'quantity' => $missingQty,
                        'unit_price' => $price,
                        'subtotal' => $subtotal,
                    ];
                }
            }

            // Actualizar estado de la solicitud
            if ($allDispatchedCompletely) {
                $consumptionRequest->status = 'despachado';
            } else {
                $consumptionRequest->status = 'despachado_parcial';

                // Generar automáticamente la Solicitud de Compra (PurchaseOrder)
                if (!empty($missingItems)) {
                    // Obtener primer proveedor activo
                    $provider = \App\Models\Provider::where('is_active', true)->first();
                    if (!$provider) {
                        $provider = \App\Models\Provider::firstOrCreate(
                            ['document_number' => '0000000000'],
                            [
                                'name' => 'Proveedor Genérico',
                                'is_active' => true,
                            ]
                        );
                    }

                    $orderTotal = 0.0;
                    foreach ($missingItems as $item) {
                        $orderTotal += $item['subtotal'];
                    }

                    $orderData = [
                        'provider_id' => $provider->id,
                        'warehouse_id' => $warehouseId,
                        'user_id' => Auth::id() ?? $consumptionRequest->user_id,
                        'date' => now()->toDateString(),
                        'notes' => "Generada automáticamente por faltantes en Solicitud de Consumo #{$consumptionRequest->id} de {$consumptionRequest->requested_by}",
                        'total' => $orderTotal,
                    ];

                    $purchaseOrderService = app(\App\Services\PurchaseOrderService::class);
                    $purchaseOrderService->create($orderData, $missingItems);
                }
            }

            $consumptionRequest->dispatched_by_user_id = Auth::id();
            $consumptionRequest->dispatched_at = now();
            $consumptionRequest->save();

            return $consumptionRequest;
        });
    }

    /**
     * Obtiene el listado de productos faltantes y sus cantidades.
     */
    public function getMissingItems(ConsumptionRequest $consumptionRequest): array
    {
        $warehouseId = $consumptionRequest->warehouse_id;
        $missingItems = [];

        foreach ($consumptionRequest->details as $detail) {
            $requestedQty = (float) $detail->quantity_requested;
            $alreadyDelivered = (float) $detail->quantity_delivered;
            $pendingQty = $requestedQty - $alreadyDelivered;

            if ($pendingQty <= 0) {
                continue;
            }

            $stock = Stock::withoutGlobalScopes()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $detail->product_id)
                ->first();

            $availableStock = $stock ? (float) $stock->quantity : 0.0;
            $missingQty = max(0.0, $pendingQty - $availableStock);

            if ($missingQty > 0) {
                $missingItems[] = [
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name,
                    'product_code' => $detail->product->code,
                    'quantity' => $missingQty,
                    'unit_price' => (float) ($detail->product->kardexes()->latest()->first()?->unit_cost ?? $detail->product->price ?? 0.0),
                ];
            }
        }

        return $missingItems;
    }
}
