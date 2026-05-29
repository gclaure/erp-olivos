<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KardexMovementType;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\Kardex;
use App\Models\Stock;
use App\Services\KardexService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class ConsumptionRequestService
{
    public function __construct(private KardexService $kardexService) {}

    /**
     * Crear una solicitud de consumo interno de forma transaccional.
     */
    public function createRequest(array $data, array $items): ConsumptionRequest
    {
        return DB::transaction(function () use ($data, $items) {
            $lastNumber = ConsumptionRequest::withoutGlobalScopes()->max('number') ?? 0;

            $request = ConsumptionRequest::create([
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => Auth::id(),
                'requested_by' => $data['requested_by'],
                'date' => $data['date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => 'pendiente',
                'number' => $lastNumber + 1,
            ]);

            foreach ($items as $item) {
                $request->details()->create([
                    'product_id' => $item['id'],
                    'quantity_requested' => $item['quantity'],
                    'quantity_delivered' => 0.0,
                ]);
            }

            return $request;
        });
    }

    /**
     * Despacha el stock físico disponible para una solicitud de consumo.
     */
    public function dispatchRequest(ConsumptionRequest $consumptionRequest): ConsumptionRequest
    {
        if (!in_array($consumptionRequest->status, ['aprobado', 'despachado_parcial'])) {
            throw new Exception("Solo se pueden despachar solicitudes que estén aprobadas por el administrador o con despacho parcial.");
        }

        return DB::transaction(function () use ($consumptionRequest) {
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
                $dispatchQty = min($pendingQty, $availableStock);

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

    /**
     * Cancela una solicitud de consumo interno.
     */
    public function cancelRequest(ConsumptionRequest $consumptionRequest): ConsumptionRequest
    {
        if (in_array($consumptionRequest->status, ['entregado', 'despachado', 'despachado_parcial'])) {
            throw new Exception("No se puede cancelar una solicitud que ya ha sido despachada o entregada.");
        }

        $consumptionRequest->status = 'cancelado';
        $consumptionRequest->save();

        return $consumptionRequest;
    }

    /**
     * Recepciona una solicitud de consumo interno (Área Solicitante).
     * 
     * @param ConsumptionRequest $consumptionRequest
     * @param array<string, float> $receivedQuantities Array asociativo [detail_id => quantity]
     * @param array<string, string> $observations Array asociativo [detail_id => observation]
     */
    public function receiveRequest(
        ConsumptionRequest $consumptionRequest,
        array $receivedQuantities = [],
        array $observations = []
    ): ConsumptionRequest {
        if (!in_array($consumptionRequest->status, ['despachado', 'despachado_parcial'])) {
            throw new Exception("Solo se pueden recepcionar solicitudes en estado despachado.");
        }

        return DB::transaction(function () use ($consumptionRequest, $receivedQuantities, $observations) {
            $consumptionRequest->loadMissing('details.product');

            foreach ($consumptionRequest->details as $detail) {
                // Si viene una cantidad específica en el array, la usamos. De lo contrario, por defecto es lo despachado.
                $qty = isset($receivedQuantities[$detail->id]) ? (float) $receivedQuantities[$detail->id] : (float) $detail->quantity_delivered;
                if ($qty < 0) {
                    throw new Exception("La cantidad recibida no puede ser negativa.");
                }

                // Validar discrepancia contra la cantidad solicitada originalmente
                $isDifferent = abs($qty - (float) $detail->quantity_requested) >= 0.01;
                $observation = $observations[$detail->id] ?? null;

                if ($isDifferent && empty(trim((string)$observation))) {
                    throw new Exception("Se requiere una observación porque la cantidad recibida para '{$detail->product?->name}' es diferente a la solicitada.");
                }

                $detail->quantity_received = $qty;
                $detail->observation = $isDifferent ? trim((string)$observation) : null;
                $detail->save();
            }

            $consumptionRequest->status = 'entregado';
            $consumptionRequest->received_by_user_id = Auth::id();
            $consumptionRequest->received_at = now();
            $consumptionRequest->save();

            return $consumptionRequest;
        });
    }

    /**
     * Aprueba una solicitud de consumo interno (Administrador).
     */
    public function approveRequest(ConsumptionRequest $consumptionRequest, string $userId, ?string $observationNotes = null): ConsumptionRequest
    {
        if (!in_array($consumptionRequest->status, ['pendiente', 'observado'])) {
            throw new Exception("Solo se pueden aprobar solicitudes en estado pendiente u observado.");
        }

        $consumptionRequest->status = 'aprobado';
        $consumptionRequest->approved_by_user_id = $userId;
        $consumptionRequest->approved_at = now();
        // Guardamos las observaciones de aprobación si se proporcionan
        $consumptionRequest->observation_notes = $observationNotes ? trim($observationNotes) : null;
        
        // Al aprobar, limpiamos observaciones previas del estado 'observado'
        $consumptionRequest->observed_by_user_id = null;
        $consumptionRequest->observed_at = null;
        $consumptionRequest->save();

        return $consumptionRequest;
    }

    /**
     * Observa una solicitud de consumo interno (Administrador).
     */
    public function observeRequest(ConsumptionRequest $consumptionRequest, string $userId, string $notes): ConsumptionRequest
    {
        if (!in_array($consumptionRequest->status, ['pendiente', 'observado'])) {
            throw new Exception("Solo se pueden observar solicitudes en estado pendiente u observado.");
        }

        if (empty(trim($notes))) {
            throw new Exception("Debe ingresar un comentario para registrar la observación.");
        }

        $consumptionRequest->status = 'observado';
        $consumptionRequest->observed_by_user_id = $userId;
        $consumptionRequest->observed_at = now();
        $consumptionRequest->observation_notes = $notes;
        
        // Limpiamos aprobación si es que hubiera una previa por alguna razón
        $consumptionRequest->approved_by_user_id = null;
        $consumptionRequest->approved_at = null;
        $consumptionRequest->save();

        return $consumptionRequest;
    }
}
