<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ConsumptionModificationStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelModificationRequest;
use App\Http\Requests\Admin\RecordModificationReturnRequest;
use App\Http\Requests\Admin\ResolveConsumptionModificationRequest;
use App\Http\Requests\Admin\StoreConsumptionModificationRequest;
use App\Http\Resources\ConsumptionRequestModificationResource;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\ConsumptionRequestModification;
use App\Models\PurchaseOrder;
use App\Services\ConsumptionRequestModificationService;
use DomainException;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsumptionRequestModificationController extends Controller
{
    public function __construct(
        private readonly ConsumptionRequestModificationService $modificationService
    ) {}

    /**
     * Bandeja administrativa de solicitudes de modificación de consumo.
     */
    public function index(Request $request): Response
    {
        $statusFilter = (string) $request->input('status', 'pendiente');
        $search = trim((string) $request->input('search', ''));
        $requestId = $request->input('consumption_request_id');

        $query = ConsumptionRequestModification::query()
            ->whereHas('consumptionRequest')
            ->with([
                'consumptionRequest.warehouse',
                'consumptionRequest.user',
                'consumptionRequest.details.product.unitOfMeasure',
                'detail.purchaseOrderDetails' => static function ($q): void {
                    $q->whereNull('cancelled_at')
                        ->with(['purchaseOrder' => static fn ($poQ) => $poQ->withoutGlobalScopes()->with(['provider', 'details'])]);
                },
                'fromProduct.unitOfMeasure',
                'fromProduct.stocks',
                'toProduct.unitOfMeasure',
                'toProduct.stocks',
                'requestedByUser',
                'resolvedByUser',
                'cancelledByUser',
                'returns.returnedByUser',
            ])
            ->orderByDesc('created_at');

        if ($statusFilter === 'abiertas') {
            $query->open();
        } elseif (in_array($statusFilter, array_column(ConsumptionModificationStatusEnum::cases(), 'value'), true)) {
            $query->where('status', $statusFilter);
        }

        if ($requestId) {
            $query->where('consumption_request_id', (string) $requestId);
        }

        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('reason', 'ilike', "%{$search}%")
                    ->orWhereHas('fromProduct', static fn ($pq) => $pq->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%"))
                    ->orWhereHas('toProduct', static fn ($pq) => $pq->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%"))
                    ->orWhereHas('consumptionRequest', static function ($crQ) use ($search): void {
                        $crQ->where('requested_by', 'ilike', "%{$search}%");
                        if (is_numeric($search)) {
                            $crQ->orWhere('number', (int) $search);
                        }
                    });
            });
        }

        $modifications = $query->paginate(15)->withQueryString();

        // Detectar órdenes legadas sin clave foránea para las modificaciones pendientes de esta página
        $pendingItems = $modifications->getCollection()->filter(
            static fn (ConsumptionRequestModification $m): bool => $m->status === ConsumptionModificationStatusEnum::Pending
        );

        if ($pendingItems->isNotEmpty()) {
            $warehouseIds = $pendingItems->map(static fn ($m) => $m->consumptionRequest?->warehouse_id)->filter()->unique()->values()->all();
            $legacyOrders = PurchaseOrder::withoutGlobalScopes()
                ->whereIn('warehouse_id', $warehouseIds)
                ->whereNull('consumption_request_id')
                ->whereIn('status', ['pendiente', 'aprobada'])
                ->whereNotNull('notes')
                ->get(['id', 'warehouse_id', 'notes']);

            foreach ($pendingItems as $mod) {
                $cr = $mod->consumptionRequest;
                if (!$cr) {
                    continue;
                }
                $hasLegacy = $legacyOrders->contains(static function (PurchaseOrder $po) use ($cr): bool {
                    return $po->warehouse_id === $cr->warehouse_id
                        && (str_contains((string) $po->notes, (string) $cr->id)
                            || str_contains((string) $po->notes, "#{$cr->formatted_number}"));
                });
                $mod->setAttribute('has_legacy_unlinked_orders_warning', $hasLegacy);
            }
        }

        $counts = [
            'pendiente' => ConsumptionRequestModification::whereHas('consumptionRequest')
                ->where('status', ConsumptionModificationStatusEnum::Pending->value)
                ->count(),
            'aprobada' => ConsumptionRequestModification::whereHas('consumptionRequest')
                ->where('status', ConsumptionModificationStatusEnum::Approved->value)
                ->count(),
            'aplicada' => ConsumptionRequestModification::whereHas('consumptionRequest')
                ->where('status', ConsumptionModificationStatusEnum::Applied->value)
                ->count(),
            'historial' => ConsumptionRequestModification::whereHas('consumptionRequest')
                ->whereIn('status', ConsumptionModificationStatusEnum::terminalValues())
                ->count(),
        ];

        return Inertia::render('Admin/ConsumptionRequest/ModificationsIndex', [
            'modifications' => ConsumptionRequestModificationResource::collection($modifications),
            'counts' => $counts,
            'filters' => [
                'status' => $statusFilter,
                'search' => $search,
                'consumption_request_id' => $requestId,
            ],
        ]);
    }

    /**
     * Registra una nueva solicitud de modificación por el almacenero.
     */
    public function store(
        StoreConsumptionModificationRequest $request,
        ConsumptionRequest $consumption_request,
        ConsumptionRequestDetail $detail
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $this->modificationService->request(
                consumptionRequest: $consumption_request,
                detail: $detail,
                toProductId: (string) $data['to_product_id'],
                requestedQuantity: (string) $data['requested_quantity'],
                reason: (string) $data['reason'],
                requestedByUserId: (string) $request->user()->id,
                quantityPrepared: array_key_exists('quantity_prepared', $data) && $data['quantity_prepared'] !== null
                    ? (string) $data['quantity_prepared']
                    : null,
                quantityPhysicallyDelivered: array_key_exists('quantity_physically_delivered', $data) && $data['quantity_physically_delivered'] !== null
                    ? (string) $data['quantity_physically_delivered']
                    : null
            );
        } catch (BroadcastException) {
            // La transacción ya confirmó en PostgreSQL; Reverb offline no revierte la creación
        } catch (DomainException|Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Solicitud de modificación enviada al administrador para revisión.');
    }

    /**
     * Aprueba una solicitud de modificación pendiente.
     */
    public function approve(
        ResolveConsumptionModificationRequest $request,
        ConsumptionRequest $consumption_request,
        ConsumptionRequestModification $modification
    ): RedirectResponse {
        if ($modification->consumption_request_id !== $consumption_request->id) {
            abort(404);
        }

        $data = $request->validated();
        $appliedImmediately = false;

        try {
            $resolved = $this->modificationService->approve(
                modification: $modification,
                resolvedByUserId: (string) $request->user()->id,
                resolutionNotes: isset($data['resolution_notes']) ? (string) $data['resolution_notes'] : null,
                purchaseResolutions: is_array($data['purchase_resolutions'] ?? null) ? $data['purchase_resolutions'] : []
            );
            $appliedImmediately = $resolved->status === ConsumptionModificationStatusEnum::Applied;
        } catch (BroadcastException) {
            $appliedImmediately = $modification->fresh()?->status === ConsumptionModificationStatusEnum::Applied;
        } catch (DomainException|Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        $msg = $appliedImmediately
            ? 'Modificación aprobada y aplicada exitosamente al detalle de la solicitud.'
            : 'Modificación aprobada. El detalle quedó bloqueado en espera de la devolución física completa.';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Rechaza una solicitud de modificación pendiente.
     */
    public function reject(
        ResolveConsumptionModificationRequest $request,
        ConsumptionRequest $consumption_request,
        ConsumptionRequestModification $modification
    ): RedirectResponse {
        if ($modification->consumption_request_id !== $consumption_request->id) {
            abort(404);
        }

        $data = $request->validated();

        try {
            $this->modificationService->reject(
                modification: $modification,
                resolvedByUserId: (string) $request->user()->id,
                resolutionNotes: (string) $data['resolution_notes']
            );
        } catch (BroadcastException) {
            // Commit ya efectuado
        } catch (DomainException|Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Solicitud de modificación rechazada. Se conserva el producto original.');
    }

    /**
     * Registra una devolución física (parcial o completa) por el almacenero.
     */
    public function confirmReturn(
        RecordModificationReturnRequest $request,
        ConsumptionRequest $consumption_request,
        ConsumptionRequestModification $modification
    ): RedirectResponse {
        if ($modification->consumption_request_id !== $consumption_request->id) {
            abort(404);
        }

        $data = $request->validated();

        try {
            $this->modificationService->confirmReturn(
                modification: $modification,
                returnedQuantity: (string) $data['returned_quantity'],
                returnedByUserId: (string) $request->user()->id,
                returnNotes: isset($data['return_notes']) ? (string) $data['return_notes'] : null
            );
        } catch (BroadcastException) {
            // Commit ya efectuado
        } catch (DomainException|Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        $isApplied = $modification->fresh()?->status === ConsumptionModificationStatusEnum::Applied;
        $msg = $isApplied
            ? 'Devolución física completada. Se aplicó el producto sustituto y se habilitó su preparación.'
            : 'Devolución parcial registrada. El detalle permanece bloqueado hasta completar la devolución física.';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Cancela administrativamente una modificación aprobada con devolución física incompleta.
     */
    public function cancel(
        CancelModificationRequest $request,
        ConsumptionRequest $consumption_request,
        ConsumptionRequestModification $modification
    ): RedirectResponse {
        if ($modification->consumption_request_id !== $consumption_request->id) {
            abort(404);
        }

        $data = $request->validated();

        try {
            $this->modificationService->cancel(
                modification: $modification,
                cancelledByUserId: (string) $request->user()->id,
                cancellationReason: (string) $data['cancellation_reason']
            );
        } catch (BroadcastException) {
            // Commit ya efectuado
        } catch (DomainException|Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->back()->with(
            'success',
            'Modificación cancelada administrativamente. El detalle restauró el producto original ajustando las unidades devueltas.'
        );
    }
}
