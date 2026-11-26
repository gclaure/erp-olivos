<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsumptionDetailFulfillmentEnum;
use App\Enums\ConsumptionModificationStatusEnum;
use App\Events\ConsumptionRequestModificationApplied;
use App\Events\ConsumptionRequestModificationRequested;
use App\Events\ConsumptionRequestModificationResolved;
use App\Events\ConsumptionRequestModificationReturnRecorded;
use App\Events\NuevaNotificacion;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\ConsumptionRequestModification;
use App\Models\ConsumptionRequestModificationReturn;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\User;
use App\Notifications\ConsumptionModificationNotification;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConsumptionRequestModificationService
{
    private const ALLOWED_REQUEST_STATUSES = [
        'aprobado',
        'despachado',
        'despachado_parcial',
    ];

    public function __construct(
        private PurchaseOrderService $purchaseOrderService,
        private ConsumptionRequestStatusResolver $statusResolver
    ) {}

    /**
     * Registra una nueva solicitud de modificación sobre un detalle de consumo aprobado.
     * Orden global de bloqueos: solicitud -> modificación -> detalle.
     */
    public function request(
        ConsumptionRequest $consumptionRequest,
        ConsumptionRequestDetail $detail,
        string $toProductId,
        float|int|string $requestedQuantity,
        string $reason,
        string $requestedByUserId,
        float|int|string|null $quantityPrepared = null,
        float|int|string|null $quantityPhysicallyDelivered = null
    ): ConsumptionRequestModification {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw new DomainException('Debe ingresar un motivo obligatorio para solicitar la modificación.');
        }

        return DB::transaction(function () use (
            $consumptionRequest,
            $detail,
            $toProductId,
            $requestedQuantity,
            $cleanReason,
            $requestedByUserId,
            $quantityPrepared,
            $quantityPhysicallyDelivered
        ): ConsumptionRequestModification {
            // 1. Bloqueo de solicitud
            $lockedRequest = ConsumptionRequest::withoutGlobalScopes()
                ->with('warehouse')
                ->lockForUpdate()
                ->findOrFail($consumptionRequest->id);

            if (!in_array($lockedRequest->status, self::ALLOWED_REQUEST_STATUSES, true)) {
                throw new DomainException(
                    "El estado actual de la solicitud ({$lockedRequest->status}) no admite solicitudes de modificación."
                );
            }

            // 2. Bloqueo de modificaciones abiertas del detalle
            $existingOpen = ConsumptionRequestModification::query()
                ->where('consumption_request_detail_id', $detail->id)
                ->open()
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($existingOpen !== null) {
                throw new DomainException('Ya existe una solicitud de modificación abierta para este detalle.');
            }

            // 3. Bloqueo del detalle
            $lockedDetail = ConsumptionRequestDetail::query()
                ->with('product.unitOfMeasure')
                ->lockForUpdate()
                ->findOrFail($detail->id);

            if ($lockedDetail->consumption_request_id !== $lockedRequest->id) {
                throw new DomainException('El detalle indicado no pertenece a esta solicitud de consumo.');
            }

            if ($lockedDetail->fulfillment_status === ConsumptionDetailFulfillmentEnum::PendingReturn) {
                throw new DomainException('Este detalle se encuentra bloqueado en espera de devolución física.');
            }

            $reqQty = BigDecimal::of((string) $requestedQuantity)->toScale(4, RoundingMode::HALF_UP);
            if ($reqQty->isLessThanOrEqualTo(0)) {
                throw new DomainException('La cantidad solicitada debe ser mayor que cero.');
            }

            $origQty = BigDecimal::of((string) $lockedDetail->quantity_requested)->toScale(4, RoundingMode::HALF_UP);

            $fromProduct = $lockedDetail->product;
            $toProduct = Product::withTrashed()->with('unitOfMeasure')->find($toProductId);

            if (!$toProduct || $toProduct->trashed() || !$toProduct->is_active) {
                throw new DomainException('El producto solicitado no existe o se encuentra inactivo.');
            }

            if ((string) ($fromProduct?->unit_of_measure_id ?? '') !== (string) ($toProduct->unit_of_measure_id ?? '')) {
                throw new DomainException('El producto solicitado debe tener la misma unidad de medida que el producto actual.');
            }

            if ($toProduct->id === $lockedDetail->product_id && $reqQty->isEqualTo($origQty)) {
                throw new DomainException('Debe cambiar al menos el producto o la cantidad respecto al detalle actual.');
            }

            $dispatchedBefore = BigDecimal::of((string) $lockedDetail->quantity_delivered)->toScale(4, RoundingMode::HALF_UP);

            if ($dispatchedBefore->isGreaterThan(0)) {
                if ($quantityPrepared === null || $quantityPhysicallyDelivered === null) {
                    throw new DomainException(
                        'Debe declarar cuántas unidades están preparadas en almacén y cuántas fueron entregadas físicamente.'
                    );
                }

                $prepDec = BigDecimal::of((string) $quantityPrepared)->toScale(4, RoundingMode::HALF_UP);
                $physDec = BigDecimal::of((string) $quantityPhysicallyDelivered)->toScale(4, RoundingMode::HALF_UP);

                if ($prepDec->isLessThan(0) || $physDec->isLessThan(0)) {
                    throw new DomainException('Las cantidades preparadas y entregadas físicamente no pueden ser negativas.');
                }

                if (!$prepDec->plus($physDec)->isEqualTo($dispatchedBefore)) {
                    throw new DomainException(
                        'La suma de unidades preparadas en almacén y entregadas físicamente debe ser exactamente igual a la cantidad despachada del detalle.'
                    );
                }
            } else {
                $prepDec = BigDecimal::zero()->toScale(4, RoundingMode::HALF_UP);
                $physDec = BigDecimal::zero()->toScale(4, RoundingMode::HALF_UP);
            }

            try {
                $modification = ConsumptionRequestModification::create([
                    'consumption_request_id' => $lockedRequest->id,
                    'consumption_request_detail_id' => $lockedDetail->id,
                    'from_product_id' => $lockedDetail->product_id,
                    'to_product_id' => $toProduct->id,
                    'original_quantity' => (string) $origQty,
                    'requested_quantity' => (string) $reqQty,
                    'quantity_dispatched_before_modification' => (string) $dispatchedBefore,
                    'quantity_prepared' => (string) $prepDec,
                    'quantity_physically_delivered' => (string) $physDec,
                    'reason' => $cleanReason,
                    'status' => ConsumptionModificationStatusEnum::Pending,
                    'requested_by_user_id' => $requestedByUserId,
                ]);
            } catch (UniqueConstraintViolationException $e) {
                throw new DomainException('Ya existe una solicitud de modificación abierta para este detalle.', 0, $e);
            } catch (QueryException $e) {
                if ((string) $e->getCode() === '23505') {
                    throw new DomainException('Ya existe una solicitud de modificación abierta para este detalle.', 0, $e);
                }
                throw $e;
            }

            event(new ConsumptionRequestModificationRequested($modification));

            DB::afterCommit(function () use ($lockedRequest, $modification, $fromProduct, $toProduct): void {
                $this->notifyAdminsOfNewModification(
                    $lockedRequest,
                    $modification,
                    $fromProduct?->name ?? 'Producto',
                    $toProduct->name
                );
            });

            return $modification->load(['fromProduct.unitOfMeasure', 'toProduct.unitOfMeasure', 'requestedByUser']);
        });
    }

    /**
     * Aprueba una modificación pendiente, resolviendo explícitamente las líneas de compra abiertas
     * y aplicando según la situación física declarada.
     * Orden global de bloqueos: solicitud -> modificación -> detalle -> compra.
     *
     * @param array<int, array{purchase_order_detail_id: string, decision: string, notes?: string|null}> $purchaseResolutions
     */
    public function approve(
        ConsumptionRequestModification $modification,
        string $resolvedByUserId,
        ?string $resolutionNotes = null,
        array $purchaseResolutions = []
    ): ConsumptionRequestModification {
        return DB::transaction(function () use (
            $modification,
            $resolvedByUserId,
            $resolutionNotes,
            $purchaseResolutions
        ): ConsumptionRequestModification {
            // 1. Bloqueo de solicitud
            $lockedRequest = ConsumptionRequest::withoutGlobalScopes()
                ->with('warehouse')
                ->lockForUpdate()
                ->findOrFail($modification->consumption_request_id);

            if (!in_array($lockedRequest->status, self::ALLOWED_REQUEST_STATUSES, true)) {
                throw new DomainException(
                    "La solicitud de consumo ya no admite modificaciones (estado actual: {$lockedRequest->status})."
                );
            }

            // 2. Bloqueo de modificación
            $lockedMod = ConsumptionRequestModification::query()
                ->lockForUpdate()
                ->findOrFail($modification->id);

            if ($lockedMod->status !== ConsumptionModificationStatusEnum::Pending) {
                throw new DomainException('Esta solicitud de modificación ya fue resuelta previamente.');
            }

            // 3. Bloqueo de detalle
            $lockedDetail = ConsumptionRequestDetail::query()
                ->lockForUpdate()
                ->findOrFail($lockedMod->consumption_request_detail_id);

            // 4. Bloqueo y resolución de líneas de compra abiertas del mismo detalle y producto original
            $openLines = $this->getOpenPurchaseLines($lockedDetail->id, $lockedMod->from_product_id, true);
            $storedResolutions = $this->resolveOpenPurchaseLines(
                $openLines,
                $purchaseResolutions,
                $lockedRequest->formatted_number
            );

            $lockedMod->quantity_dispatched_before_modification = (string) BigDecimal::of(
                (string) $lockedDetail->quantity_delivered
            )->toScale(4, RoundingMode::HALF_UP);

            $physDelivered = BigDecimal::of((string) $lockedMod->quantity_physically_delivered)
                ->toScale(4, RoundingMode::HALF_UP);

            $cleanNotes = $resolutionNotes !== null && trim($resolutionNotes) !== ''
                ? trim($resolutionNotes)
                : null;

            $appliedImmediately = false;

            if ($physDelivered->isEqualTo(0)) {
                // Sin entrega física (0 despachado o solo preparado en almacén): cancelar preparación y aplicar ya
                $lockedDetail->product_id = $lockedMod->to_product_id;
                $lockedDetail->quantity_requested = $lockedMod->requested_quantity;
                $lockedDetail->quantity_delivered = '0.0000';
                $lockedDetail->fulfillment_status = ConsumptionDetailFulfillmentEnum::Normal;
                $lockedDetail->save();

                $lockedMod->status = ConsumptionModificationStatusEnum::Applied;
                $lockedMod->resolved_by_user_id = $resolvedByUserId;
                $lockedMod->resolved_at = now();
                $lockedMod->resolution_notes = $cleanNotes;
                $lockedMod->purchase_resolutions = $storedResolutions;
                $lockedMod->applied_at = now();
                $lockedMod->save();

                $appliedImmediately = true;
            } else {
                // Con unidades entregadas físicamente: conservar producto original, bloquear y esperar devolución
                $lockedDetail->quantity_delivered = (string) $physDelivered;
                $lockedDetail->fulfillment_status = ConsumptionDetailFulfillmentEnum::PendingReturn;
                $lockedDetail->save();

                $lockedMod->status = ConsumptionModificationStatusEnum::Approved;
                $lockedMod->resolved_by_user_id = $resolvedByUserId;
                $lockedMod->resolved_at = now();
                $lockedMod->resolution_notes = $cleanNotes;
                $lockedMod->purchase_resolutions = $storedResolutions;
                $lockedMod->save();
            }

            $lockedRequest->status = $this->statusResolver->resolve($lockedRequest);
            $lockedRequest->save();

            event(new ConsumptionRequestModificationResolved($lockedMod, 'approved'));

            if ($appliedImmediately) {
                event(new ConsumptionRequestModificationApplied($lockedMod));
            }

            DB::afterCommit(function () use ($lockedRequest, $lockedMod, $appliedImmediately): void {
                $statusMsg = $appliedImmediately
                    ? 'fue aprobada y aplicada inmediatamente'
                    : 'fue aprobada y aguarda el registro de la devolución física';

                $this->notifyRequester(
                    $lockedRequest,
                    $lockedMod,
                    'Modificación Aprobada',
                    "Tu solicitud de modificación en el consumo #{$lockedRequest->formatted_number} {$statusMsg}.",
                    'consumption_modification_approved'
                );
            });

            return $lockedMod->fresh([
                'fromProduct.unitOfMeasure',
                'toProduct.unitOfMeasure',
                'requestedByUser',
                'resolvedByUser',
                'returns.returnedByUser',
            ]) ?? $lockedMod;
        });
    }

    /**
     * Rechaza una modificación pendiente con nota obligatoria, sin alterar el detalle original.
     * Orden global de bloqueos: solicitud -> modificación -> detalle.
     */
    public function reject(
        ConsumptionRequestModification $modification,
        string $resolvedByUserId,
        string $resolutionNotes
    ): ConsumptionRequestModification {
        $cleanNotes = trim($resolutionNotes);
        if ($cleanNotes === '') {
            throw new DomainException('Debe ingresar una nota de resolución obligatoria al rechazar la modificación.');
        }

        return DB::transaction(function () use ($modification, $resolvedByUserId, $cleanNotes): ConsumptionRequestModification {
            $lockedRequest = ConsumptionRequest::withoutGlobalScopes()
                ->with('warehouse')
                ->lockForUpdate()
                ->findOrFail($modification->consumption_request_id);

            $lockedMod = ConsumptionRequestModification::query()
                ->lockForUpdate()
                ->findOrFail($modification->id);

            if ($lockedMod->status !== ConsumptionModificationStatusEnum::Pending) {
                throw new DomainException('Esta solicitud de modificación ya fue resuelta previamente.');
            }

            ConsumptionRequestDetail::query()
                ->lockForUpdate()
                ->findOrFail($lockedMod->consumption_request_detail_id);

            $lockedMod->status = ConsumptionModificationStatusEnum::Rejected;
            $lockedMod->resolved_by_user_id = $resolvedByUserId;
            $lockedMod->resolved_at = now();
            $lockedMod->resolution_notes = $cleanNotes;
            $lockedMod->save();

            event(new ConsumptionRequestModificationResolved($lockedMod, 'rejected'));

            DB::afterCommit(function () use ($lockedRequest, $lockedMod, $cleanNotes): void {
                $this->notifyRequester(
                    $lockedRequest,
                    $lockedMod,
                    'Modificación Rechazada',
                    "Tu solicitud de modificación en el consumo #{$lockedRequest->formatted_number} fue rechazada: {$cleanNotes}",
                    'consumption_modification_rejected'
                );
            });

            return $lockedMod->fresh([
                'fromProduct.unitOfMeasure',
                'toProduct.unitOfMeasure',
                'requestedByUser',
                'resolvedByUser',
            ]) ?? $lockedMod;
        });
    }

    /**
     * Registra una devolución física (parcial o total) sobre una modificación aprobada.
     * Solo aplica la sustitución cuando el acumulado devuelto iguala lo entregado físicamente.
     * Orden global de bloqueos: solicitud -> modificación -> detalle.
     */
    public function confirmReturn(
        ConsumptionRequestModification $modification,
        float|int|string $returnedQuantity,
        string $returnedByUserId,
        ?string $returnNotes = null
    ): ConsumptionRequestModificationReturn {
        $retDec = BigDecimal::of((string) $returnedQuantity)->toScale(4, RoundingMode::HALF_UP);
        if ($retDec->isLessThanOrEqualTo(0)) {
            throw new DomainException('La cantidad devuelta debe ser mayor que cero.');
        }

        return DB::transaction(function () use (
            $modification,
            $retDec,
            $returnedByUserId,
            $returnNotes
        ): ConsumptionRequestModificationReturn {
            $lockedRequest = ConsumptionRequest::withoutGlobalScopes()
                ->with('warehouse')
                ->lockForUpdate()
                ->findOrFail($modification->consumption_request_id);

            if (!in_array($lockedRequest->status, self::ALLOWED_REQUEST_STATUSES, true)) {
                throw new DomainException(
                    "El estado actual de la solicitud ({$lockedRequest->status}) no permite registrar devoluciones."
                );
            }

            $lockedMod = ConsumptionRequestModification::query()
                ->lockForUpdate()
                ->findOrFail($modification->id);

            if ($lockedMod->status !== ConsumptionModificationStatusEnum::Approved) {
                throw new DomainException(
                    'Solo se pueden registrar devoluciones sobre modificaciones aprobadas en espera de devolución.'
                );
            }

            $lockedDetail = ConsumptionRequestDetail::query()
                ->lockForUpdate()
                ->findOrFail($lockedMod->consumption_request_detail_id);

            $previousReturns = ConsumptionRequestModificationReturn::query()
                ->where('consumption_request_modification_id', $lockedMod->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'returned_quantity']);

            $alreadyReturnedDec = BigDecimal::zero();
            foreach ($previousReturns as $prev) {
                $alreadyReturnedDec = $alreadyReturnedDec->plus(BigDecimal::of((string) $prev->returned_quantity));
            }

            $physDeliveredDec = BigDecimal::of((string) $lockedMod->quantity_physically_delivered)
                ->toScale(4, RoundingMode::HALF_UP);

            $newTotalReturnedDec = $alreadyReturnedDec->plus($retDec);

            if ($newTotalReturnedDec->isGreaterThan($physDeliveredDec)) {
                $pendingReturnDec = $physDeliveredDec->minus($alreadyReturnedDec)->toScale(4, RoundingMode::HALF_UP);
                throw new DomainException(
                    "La cantidad devuelta ({$retDec}) supera el saldo pendiente de devolución ({$pendingReturnDec})."
                );
            }

            $cleanNotes = $returnNotes !== null && trim($returnNotes) !== '' ? trim($returnNotes) : null;

            $returnRecord = ConsumptionRequestModificationReturn::create([
                'consumption_request_modification_id' => $lockedMod->id,
                'returned_quantity' => (string) $retDec,
                'returned_by_user_id' => $returnedByUserId,
                'returned_at' => now(),
                'return_notes' => $cleanNotes,
            ]);

            $isComplete = $newTotalReturnedDec->isEqualTo($physDeliveredDec);

            if ($isComplete) {
                $lockedDetail->product_id = $lockedMod->to_product_id;
                $lockedDetail->quantity_requested = $lockedMod->requested_quantity;
                $lockedDetail->quantity_delivered = '0.0000';
                $lockedDetail->fulfillment_status = ConsumptionDetailFulfillmentEnum::Normal;
                $lockedDetail->save();

                $lockedMod->status = ConsumptionModificationStatusEnum::Applied;
                $lockedMod->applied_at = now();
                $lockedMod->save();

                $lockedRequest->status = $this->statusResolver->resolve($lockedRequest);
                $lockedRequest->save();
            }

            event(new ConsumptionRequestModificationReturnRecorded($lockedMod, $returnRecord, $isComplete));

            if ($isComplete) {
                event(new ConsumptionRequestModificationApplied($lockedMod));
            }

            return $returnRecord->load('returnedByUser');
        });
    }

    /**
     * Cancela administrativamente una modificación aprobada cuya devolución física quedó incompleta.
     * Restaura el detalle a normal con el producto original y reduce quantity_delivered por lo devuelto.
     * Orden global de bloqueos: solicitud -> modificación -> detalle.
     */
    public function cancel(
        ConsumptionRequestModification $modification,
        string $cancelledByUserId,
        string $cancellationReason
    ): ConsumptionRequestModification {
        $cleanReason = trim($cancellationReason);
        if ($cleanReason === '') {
            throw new DomainException('Debe ingresar un motivo obligatorio para cancelar la modificación.');
        }

        return DB::transaction(function () use (
            $modification,
            $cancelledByUserId,
            $cleanReason
        ): ConsumptionRequestModification {
            $lockedRequest = ConsumptionRequest::withoutGlobalScopes()
                ->with('warehouse')
                ->lockForUpdate()
                ->findOrFail($modification->consumption_request_id);

            $lockedMod = ConsumptionRequestModification::query()
                ->lockForUpdate()
                ->findOrFail($modification->id);

            if ($lockedMod->status !== ConsumptionModificationStatusEnum::Approved) {
                throw new DomainException(
                    'Solo se pueden cancelar administrativamente modificaciones aprobadas con devolución pendiente.'
                );
            }

            $lockedDetail = ConsumptionRequestDetail::query()
                ->lockForUpdate()
                ->findOrFail($lockedMod->consumption_request_detail_id);

            $returns = ConsumptionRequestModificationReturn::query()
                ->where('consumption_request_modification_id', $lockedMod->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'returned_quantity']);

            $totalReturnedDec = BigDecimal::zero();
            foreach ($returns as $ret) {
                $totalReturnedDec = $totalReturnedDec->plus(BigDecimal::of((string) $ret->returned_quantity));
            }

            $physDeliveredDec = BigDecimal::of((string) $lockedMod->quantity_physically_delivered)
                ->toScale(4, RoundingMode::HALF_UP);

            $remainingDeliveredDec = $physDeliveredDec->minus($totalReturnedDec);
            if ($remainingDeliveredDec->isLessThan(0)) {
                $remainingDeliveredDec = BigDecimal::zero();
            }

            $lockedDetail->product_id = $lockedMod->from_product_id;
            $lockedDetail->quantity_delivered = (string) $remainingDeliveredDec->toScale(4, RoundingMode::HALF_UP);
            $lockedDetail->fulfillment_status = ConsumptionDetailFulfillmentEnum::Normal;
            $lockedDetail->save();

            $lockedMod->status = ConsumptionModificationStatusEnum::Cancelled;
            $lockedMod->cancelled_by_user_id = $cancelledByUserId;
            $lockedMod->cancelled_at = now();
            $lockedMod->cancellation_reason = $cleanReason;
            $lockedMod->save();

            $lockedRequest->status = $this->statusResolver->resolve($lockedRequest);
            $lockedRequest->save();

            event(new ConsumptionRequestModificationResolved($lockedMod, 'cancelled'));

            DB::afterCommit(function () use ($lockedRequest, $lockedMod, $cleanReason): void {
                $this->notifyRequester(
                    $lockedRequest,
                    $lockedMod,
                    'Modificación Cancelada',
                    "La modificación en el consumo #{$lockedRequest->formatted_number} fue cancelada administrativamente: {$cleanReason}",
                    'consumption_modification_cancelled'
                );
            });

            return $lockedMod->fresh([
                'fromProduct.unitOfMeasure',
                'toProduct.unitOfMeasure',
                'requestedByUser',
                'resolvedByUser',
                'cancelledByUser',
                'returns.returnedByUser',
            ]) ?? $lockedMod;
        });
    }

    /**
     * Obtiene las líneas de órdenes de compra abiertas asociadas a un detalle y producto específicos.
     *
     * @return Collection<int, PurchaseOrderDetail>
     */
    public function getOpenPurchaseLines(
        string $consumptionRequestDetailId,
        string $productId,
        bool $lock = false
    ): Collection {
        $baseQuery = PurchaseOrderDetail::query()
            ->where('consumption_request_detail_id', $consumptionRequestDetailId)
            ->where('product_id', $productId)
            ->whereNull('cancelled_at')
            ->whereHas('purchaseOrder', static function ($q): void {
                $q->withoutGlobalScopes()->whereIn('status', ['pendiente', 'aprobada']);
            })
            ->with(['purchaseOrder' => static fn ($q) => $q->withoutGlobalScopes()->with('provider')])
            ->orderBy('id');

        if (!$lock) {
            return $baseQuery->get();
        }

        $orderIds = (clone $baseQuery)->pluck('purchase_order_id')->unique()->sort()->values()->all();
        if (!empty($orderIds)) {
            PurchaseOrder::withoutGlobalScopes()
                ->whereIn('id', $orderIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);
        }

        return $baseQuery->lockForUpdate()->get();
    }

    /**
     * @param Collection<int, PurchaseOrderDetail> $openLines
     * @param array<int, array{purchase_order_detail_id: string, decision: string, notes?: string|null}> $purchaseResolutions
     * @return array<int, array<string, float|string|null>>
     */
    private function resolveOpenPurchaseLines(
        Collection $openLines,
        array $purchaseResolutions,
        string $formattedRequestNumber
    ): array {
        $resolutionsByLineId = [];
        foreach ($purchaseResolutions as $res) {
            $lineId = (string) ($res['purchase_order_detail_id'] ?? '');
            if ($lineId !== '') {
                $resolutionsByLineId[$lineId] = $res;
            }
        }

        $openLineIds = $openLines->pluck('id')->all();

        foreach (array_keys($resolutionsByLineId) as $providedLineId) {
            if (!in_array($providedLineId, $openLineIds, true)) {
                throw new DomainException(
                    'Se incluyó una resolución de compra que no pertenece a las líneas abiertas de este detalle y producto.'
                );
            }
        }

        if ($openLines->isEmpty()) {
            return [];
        }

        $unresolvedIds = [];
        foreach ($openLines as $line) {
            if (!isset($resolutionsByLineId[$line->id])) {
                $unresolvedIds[] = $line->id;
            }
        }

        if (!empty($unresolvedIds)) {
            throw new DomainException(
                'Debe resolver todas las líneas de compra abiertas asociadas antes de aprobar la modificación.'
            );
        }

        $storedResolutions = [];

        foreach ($openLines as $line) {
            $resolution = $resolutionsByLineId[$line->id];
            $decision = (string) ($resolution['decision'] ?? '');
            $notes = isset($resolution['notes']) ? trim((string) $resolution['notes']) : '';

            if (!in_array($decision, ['cancelar', 'mantener'], true)) {
                throw new DomainException("Decisión inválida ('{$decision}') para la línea de compra.");
            }

            if ($decision === 'mantener' && $notes === '') {
                throw new DomainException(
                    'Debe ingresar una nota obligatoria justificando por qué se mantiene la línea de compra del producto original.'
                );
            }

            if ($decision === 'cancelar') {
                $cancelReason = $notes !== ''
                    ? $notes
                    : "Cancelada por aprobación de modificación en Solicitud de Consumo #{$formattedRequestNumber}";

                $this->purchaseOrderService->cancelDetailLine($line, $cancelReason);
            }

            $storedResolutions[] = [
                'purchase_order_id' => $line->purchase_order_id,
                'purchase_order_detail_id' => $line->id,
                'product_id' => $line->product_id,
                'quantity' => (float) $line->quantity,
                'decision' => $decision,
                'notes' => $notes !== '' ? $notes : null,
                'resolved_at' => now()->toIso8601String(),
            ];
        }

        return $storedResolutions;
    }

    private function notifyAdminsOfNewModification(
        ConsumptionRequest $request,
        ConsumptionRequestModification $modification,
        string $fromProductName,
        string $toProductName
    ): void {
        $branchId = $request->warehouse?->branch_id;

        $adminIds = User::query()
            ->where('is_active', true)
            ->where(static function ($q) use ($branchId): void {
                $q->where('is_super_admin', true)
                    ->orWhere(static function ($sub) use ($branchId): void {
                        $sub->whereHas('roles', static function ($rq): void {
                            $rq->whereIn('name', ['Admin', 'admin', 'Administrador', 'administrador']);
                        });
                        if ($branchId) {
                            $sub->where('branch_id', $branchId);
                        }
                    });
            })
            ->pluck('id')
            ->unique()
            ->all();

        $message = "Nueva solicitud de modificación en Consumo #{$request->formatted_number}: cambiar '{$fromProductName}' por '{$toProductName}'.";
        $url = route('admin.consumption-modifications.index', [], false);

        $notification = new ConsumptionModificationNotification(
            $request->id,
            $modification->id,
            'Solicitud de Modificación de Consumo',
            $message,
            'consumption_modification_requested',
            $url
        );

        foreach ($adminIds as $adminId) {
            if ((string) $adminId === (string) $modification->requested_by_user_id) {
                continue;
            }
            $recipient = User::find($adminId);
            if (!$recipient) {
                continue;
            }
            $recipient->notify($notification);

            try {
                NuevaNotificacion::dispatch((string) $recipient->id, $message, 'consumption_modification_requested');
            } catch (Throwable) {
                // Silencioso si Reverb no está disponible
            }
        }
    }

    private function notifyRequester(
        ConsumptionRequest $request,
        ConsumptionRequestModification $modification,
        string $title,
        string $message,
        string $eventType
    ): void {
        $requester = User::find($modification->requested_by_user_id);
        if (!$requester || !$requester->is_active) {
            return;
        }

        $url = route('admin.consumption-requests.show', ['consumption_request' => $request->id], false);

        $requester->notify(new ConsumptionModificationNotification(
            $request->id,
            $modification->id,
            $title,
            $message,
            $eventType,
            $url
        ));

        try {
            NuevaNotificacion::dispatch((string) $requester->id, $message, $eventType);
        } catch (Throwable) {
            // Silencioso si Reverb no está disponible
        }
    }
}
