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
     * Despacha el stock físico disponible para una solicitud de consumo.
     */
    public function dispatchRequest(ConsumptionRequest $consumptionRequest, array $dispatchQuantities = [], array $observations = []): ConsumptionRequest
    {
        return $this->dispatchService->dispatch($consumptionRequest, $dispatchQuantities, $observations);
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
