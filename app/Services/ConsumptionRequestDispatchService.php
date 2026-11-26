<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\ConsumptionRequestModification;
use App\Models\Provider;
use App\Models\PurchaseOrderDetail;
use App\Models\Stock;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsumptionRequestDispatchService
{
    public function __construct(
        private KardexService $kardexService,
        private ConsumptionRequestStatusResolver $statusResolver
    ) {}

    /**
     * Despacha el stock físico disponible para una solicitud de consumo.
     * Orden global de bloqueos: solicitud -> modificación -> detalle -> compra.
     *
     * @param array<string, float|int|string> $dispatchQuantities
     * @param array<string, string|null> $observations
     */
    public function dispatch(
        ConsumptionRequest $consumptionRequest,
        array $dispatchQuantities = [],
        array $observations = [],
        ?string $dispatchObservation = null
    ): ConsumptionRequest {
        return DB::transaction(function () use ($consumptionRequest, $dispatchQuantities, $observations, $dispatchObservation): ConsumptionRequest {
            // 1. Bloqueo de solicitud
            $lockedRequest = ConsumptionRequest::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($consumptionRequest->id);

            if (!in_array($lockedRequest->status, ['aprobado', 'despachado_parcial'], true)) {
                throw new Exception('Solo se pueden despachar solicitudes aprobadas o con despacho parcial.');
            }

            // 2. Bloqueo de modificaciones abiertas de la solicitud
            $openModDetailIds = ConsumptionRequestModification::query()
                ->where('consumption_request_id', $lockedRequest->id)
                ->open()
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('consumption_request_detail_id')
                ->all();

            // 3. Bloqueo de detalles en orden determinístico
            $details = ConsumptionRequestDetail::query()
                ->where('consumption_request_id', $lockedRequest->id)
                ->with('product')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $warehouseId = $lockedRequest->warehouse_id;
            $missingItems = [];

            foreach ($details as $detail) {
                $hasOpenModification = in_array($detail->id, $openModDetailIds, true);
                $isPendingReturn = $detail->fulfillment_status === ConsumptionDetailFulfillmentEnum::PendingReturn;

                if ($hasOpenModification || $isPendingReturn) {
                    $attemptedQty = isset($dispatchQuantities[$detail->id])
                        ? (float) $dispatchQuantities[$detail->id]
                        : 0.0;

                    if ($attemptedQty > 0.00001) {
                        $reason = $isPendingReturn ? 'devolución física pendiente' : 'modificación abierta';
                        throw new DomainException(
                            "No se puede despachar el producto '{$detail->product?->name}' mientras tenga una {$reason}."
                        );
                    }

                    continue;
                }

                $productId = $detail->product_id;
                $requestedQty = (float) $detail->quantity_requested;
                $alreadyDelivered = (float) $detail->quantity_delivered;
                $pendingQty = $requestedQty - $alreadyDelivered;

                if ($pendingQty <= 0.00001) {
                    if ($detail->fulfillment_status === ConsumptionDetailFulfillmentEnum::PendingStock) {
                        $detail->fulfillment_status = ConsumptionDetailFulfillmentEnum::Normal;
                        $detail->save();
                    }
                    continue;
                }

                $isInventoriable = $detail->product ? $detail->product->isInventoriable() : true;

                if ($isInventoriable) {
                    $stock = Stock::withoutGlobalScopes()
                        ->where('warehouse_id', $warehouseId)
                        ->where('product_id', $productId)
                        ->first();

                    $availableStock = $stock ? (float) $stock->quantity : 0.0;
                } else {
                    $availableStock = 999999.0;
                }

                if (array_key_exists($detail->id, $dispatchQuantities)) {
                    $dispatchQty = (float) $dispatchQuantities[$detail->id];
                } else {
                    $dispatchQty = min($pendingQty, $availableStock);
                }

                if ($dispatchQty < 0) {
                    throw new Exception("La cantidad a despachar para '{$detail->product?->name}' no puede ser negativa.");
                }

                $exceedsPending = ($dispatchQty - $pendingQty) > 0.0001;
                $exceedsStock = $isInventoriable && (($dispatchQty - $availableStock) > 0.0001);

                if ($exceedsPending || $exceedsStock) {
                    $obs = $observations[$detail->id] ?? null;
                    if (empty(trim((string) $obs)) || strlen(trim((string) $obs)) < 3) {
                        $msg = $exceedsStock
                            ? "Debe ingresar una observación/motivo de al menos 3 caracteres justificando el despacho para '{$detail->product?->name}' porque supera el stock disponible ({$availableStock})."
                            : "Debe ingresar una observación/motivo de al menos 3 caracteres justificando el despacho para '{$detail->product?->name}' porque supera la cantidad pendiente ({$pendingQty}).";
                        throw new Exception($msg);
                    }
                    $detail->observation = trim((string) $obs);
                } else {
                    $obs = $observations[$detail->id] ?? null;
                    if (!empty($obs)) {
                        $detail->observation = trim((string) $obs);
                    }
                }

                $newDelivered = $alreadyDelivered + $dispatchQty;
                $missingQty = max(0.0, $requestedQty - $newDelivered);

                if ($dispatchQty > 0) {
                    $detail->quantity_delivered = $newDelivered;
                }

                // Ciclo de vida de pending_stock:
                // - Si queda completo o recibió unidades en este despacho -> normal
                // - Si sigue con faltante y stock disponible es 0 -> pending_stock
                if ($missingQty <= 0.00001 || $dispatchQty > 0.00001) {
                    $detail->fulfillment_status = ConsumptionDetailFulfillmentEnum::Normal;
                } elseif ($availableStock <= 0.00001) {
                    $detail->fulfillment_status = ConsumptionDetailFulfillmentEnum::PendingStock;
                }

                $detail->save();

                if ($missingQty > 0.00001) {
                    // 4. Bloqueo de líneas de compra abiertas asociadas al mismo detalle y producto
                    $openPoLines = PurchaseOrderDetail::query()
                        ->where('consumption_request_detail_id', $detail->id)
                        ->where('product_id', $productId)
                        ->whereNull('cancelled_at')
                        ->whereHas('purchaseOrder', static function ($q): void {
                            $q->withoutGlobalScopes()->whereIn('status', ['pendiente', 'aprobada']);
                        })
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get(['id', 'quantity']);

                    $coveredDecimal = BigDecimal::zero();
                    foreach ($openPoLines as $poLine) {
                        $coveredDecimal = $coveredDecimal->plus(BigDecimal::of((string) $poLine->quantity));
                    }

                    $missingDecimal = BigDecimal::of((string) $missingQty)->toScale(4, RoundingMode::HALF_UP);
                    $uncoveredDecimal = $missingDecimal->minus($coveredDecimal);

                    if ($uncoveredDecimal->isGreaterThan(BigDecimal::of('0.0001'))) {
                        $uncoveredQty = (float) (string) $uncoveredDecimal->toScale(4, RoundingMode::HALF_UP);
                        $price = (float) ($detail->product?->kardexes()->latest()->first()?->unit_cost ?? $detail->product?->price ?? 0.0);
                        $subtotal = $uncoveredQty * $price;

                        $missingItems[] = [
                            'product_id' => $productId,
                            'consumption_request_detail_id' => $detail->id,
                            'quantity' => $uncoveredQty,
                            'unit_price' => $price,
                            'subtotal' => $subtotal,
                        ];
                    }
                }
            }

            if (!empty($missingItems)) {
                $provider = Provider::where('is_active', true)->first();
                if (!$provider) {
                    $provider = Provider::firstOrCreate(
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
                    'user_id' => Auth::id() ?? $lockedRequest->user_id,
                    'consumption_request_id' => $lockedRequest->id,
                    'date' => now()->toDateString(),
                    'notes' => "Generada automáticamente por faltantes en Solicitud de Consumo #{$lockedRequest->formatted_number} de {$lockedRequest->requested_by}",
                    'total' => $orderTotal,
                ];

                $purchaseOrderService = app(PurchaseOrderService::class);
                $purchaseOrderService->create($orderData, $missingItems);
            }

            $newStatus = $this->statusResolver->resolve($lockedRequest, $details);
            $lockedRequest->status = $newStatus;

            if (in_array($newStatus, ['despachado', 'despachado_parcial'], true)) {
                $lockedRequest->dispatched_by_user_id = Auth::id();
                $lockedRequest->dispatched_at = now();
            }

            if ($dispatchObservation !== null && trim($dispatchObservation) !== '') {
                $lockedRequest->dispatch_observation = trim($dispatchObservation);
            }

            $lockedRequest->save();

            return $lockedRequest->fresh(['warehouse', 'user', 'details.product']);
        });
    }

    /**
     * Obtiene el listado de productos faltantes y sus cantidades no cubiertas por compras abiertas.
     *
     * @return array<int, array<string, float|string|null>>
     */
    public function getMissingItems(ConsumptionRequest $consumptionRequest): array
    {
        $warehouseId = $consumptionRequest->warehouse_id;
        $missingItems = [];

        foreach ($consumptionRequest->details as $detail) {
            if ($detail->fulfillment_status === ConsumptionDetailFulfillmentEnum::PendingReturn) {
                continue;
            }

            $requestedQty = (float) $detail->quantity_requested;
            $alreadyDelivered = (float) $detail->quantity_delivered;
            $pendingQty = $requestedQty - $alreadyDelivered;

            if ($pendingQty <= 0.00001) {
                continue;
            }

            $stock = Stock::withoutGlobalScopes()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $detail->product_id)
                ->first();

            $availableStock = $stock ? (float) $stock->quantity : 0.0;
            $missingQty = max(0.0, $pendingQty - $availableStock);

            if ($missingQty > 0.00001) {
                $coveredQty = (float) PurchaseOrderDetail::query()
                    ->where('consumption_request_detail_id', $detail->id)
                    ->where('product_id', $detail->product_id)
                    ->whereNull('cancelled_at')
                    ->whereHas('purchaseOrder', static function ($q): void {
                        $q->withoutGlobalScopes()->whereIn('status', ['pendiente', 'aprobada']);
                    })
                    ->sum('quantity');

                $uncoveredQty = max(0.0, $missingQty - $coveredQty);

                if ($uncoveredQty > 0.00001) {
                    $missingItems[] = [
                        'product_id' => $detail->product_id,
                        'consumption_request_detail_id' => $detail->id,
                        'product_name' => $detail->product?->name,
                        'product_code' => $detail->product?->code,
                        'quantity' => $uncoveredQty,
                        'unit_price' => (float) ($detail->product?->kardexes()->latest()->first()?->unit_cost ?? $detail->product?->price ?? 0.0),
                    ];
                }
            }
        }

        return $missingItems;
    }
}
