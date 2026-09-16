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
    public function __construct(
        private KardexService $kardexService,
        private ConsumptionRequestDispatchService $dispatchService
    ) {}

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
     * Actualiza una solicitud de consumo existente (solo si está pendiente y no ha sido aprobada).
     */
    public function updateRequest(ConsumptionRequest $consumptionRequest, array $data, array $items): ConsumptionRequest
    {
        return DB::transaction(function () use ($consumptionRequest, $data, $items) {
            if ($consumptionRequest->status !== 'pendiente' || !is_null($consumptionRequest->approved_at)) {
                throw new Exception("Solo se pueden modificar solicitudes en estado pendiente de aprobación.");
            }

            $user = Auth::user();
            if ($consumptionRequest->user_id !== $user?->id && !$user?->is_super_admin) {
                throw new Exception("Solo el creador de la solicitud puede modificarla.");
            }

            if (empty($items)) {
                throw new Exception("La solicitud debe contener al menos un producto.");
            }

            $consumptionRequest->update([
                'notes' => $data['notes'] ?? $consumptionRequest->notes,
                'date' => $data['date'] ?? $consumptionRequest->date,
                'requested_by' => $data['requested_by'] ?? $consumptionRequest->requested_by,
            ]);

            $newItemProductIds = collect($items)->pluck('id')->toArray();

            // Eliminar detalles que ya no están en la solicitud
            $consumptionRequest->details()->whereNotIn('product_id', $newItemProductIds)->delete();

            // Actualizar existentes o crear nuevos
            foreach ($items as $item) {
                $detail = $consumptionRequest->details()->where('product_id', $item['id'])->first();
                if ($detail) {
                    $detail->update([
                        'quantity_requested' => $item['quantity'],
                    ]);
                } else {
                    $consumptionRequest->details()->create([
                        'product_id' => $item['id'],
                        'quantity_requested' => $item['quantity'],
                        'quantity_delivered' => 0.0,
                    ]);
                }
            }

            return $consumptionRequest->fresh(['warehouse', 'user', 'details.product']);
        });
    }

    /**
     * Despacha el stock físico disponible para una solicitud de consumo.
     */
    public function dispatchRequest(
        ConsumptionRequest $consumptionRequest,
        array $dispatchQuantities = [],
        array $observations = [],
        ?string $dispatchObservation = null
    ): ConsumptionRequest {
        return $this->dispatchService->dispatch($consumptionRequest, $dispatchQuantities, $observations, $dispatchObservation);
    }

    /**
     * Obtiene el listado de productos faltantes y sus cantidades.
     */
    public function getMissingItems(ConsumptionRequest $consumptionRequest): array
    {
        return $this->dispatchService->getMissingItems($consumptionRequest);
    }

    /**
     * Cancela una solicitud de consumo interno.
     */
    public function cancelRequest(ConsumptionRequest $consumptionRequest, string $notes): ConsumptionRequest
    {
        if (in_array($consumptionRequest->status, ['entregado', 'despachado', 'despachado_parcial'])) {
            throw new Exception("No se puede cancelar una solicitud que ya ha sido despachada o entregada.");
        }

        $consumptionRequest->status = 'cancelado';
        $consumptionRequest->cancelled_by_user_id = Auth::id();
        $consumptionRequest->cancelled_at = now();
        $consumptionRequest->cancellation_notes = trim($notes);
        $consumptionRequest->save();

        return $consumptionRequest;
    }

    /**
     * Actualiza la cantidad solicitada de un producto en la solicitud (Administrador).
     */
    public function updateDetailQuantity(
        ConsumptionRequest $consumptionRequest,
        ConsumptionRequestDetail $detail,
        float $newQuantity,
        ?string $notes = null
    ): ConsumptionRequestDetail {
        if (!in_array($consumptionRequest->status, ['pendiente', 'observado'])) {
            throw new Exception("Solo se puede modificar la cantidad solicitada de productos en estado pendiente u observado.");
        }

        if ($detail->consumption_request_id !== $consumptionRequest->id) {
            throw new Exception("El producto no pertenece a esta solicitud de consumo.");
        }

        if ($newQuantity <= 0) {
            throw new Exception("La cantidad solicitada debe ser mayor a 0.");
        }

        $detail->quantity_requested = $newQuantity;
        if (!empty($notes)) {
            $detail->observation = trim($notes);
        }
        $detail->save();

        return $detail;
    }

    /**
     * Recepciona una solicitud de consumo interno (Área Solicitante).
     * 
     * @param ConsumptionRequest $consumptionRequest
     * @param array<string, float> $receivedQuantities Array asociativo [detail_id => quantity]
     * @param array<string, string> $observations Array asociativo [detail_id => observation]
     * @param array<string, string> $receiveObservations Array asociativo [detail_id => receive_observation]
     */
    public function receiveRequest(
        ConsumptionRequest $consumptionRequest,
        array $receivedQuantities = [],
        array $observations = [],
        array $receiveObservations = []
    ): ConsumptionRequest {
        if (!in_array($consumptionRequest->status, ['despachado', 'despachado_parcial'])) {
            throw new Exception("Solo se pueden recepcionar solicitudes en estado despachado.");
        }

        return DB::transaction(function () use ($consumptionRequest, $receivedQuantities, $observations, $receiveObservations) {
            $consumptionRequest->loadMissing('details.product');

            foreach ($consumptionRequest->details as $detail) {
                $qty = isset($receivedQuantities[$detail->id]) ? (float) $receivedQuantities[$detail->id] : (float) $detail->quantity_delivered;
                if ($qty < 0) {
                    throw new Exception("La cantidad recibida no puede ser negativa.");
                }

                $isDifferent = abs($qty - (float) $detail->quantity_requested) >= 0.01;
                $observation = $observations[$detail->id] ?? null;

                if ($isDifferent && empty(trim((string)$observation))) {
                    throw new Exception("Se requiere una observación porque la cantidad recibida para '{$detail->product?->name}' es diferente a la solicitada.");
                }

                $detail->quantity_received = $qty;
                $detail->observation = $isDifferent ? trim((string)$observation) : $detail->observation;
                $detail->receive_observation = trim((string)($receiveObservations[$detail->id] ?? '')) ?: null;
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
