<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveConsumptionRequest;
use App\Http\Resources\ConsumptionRequestResource;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\Warehouse;
use App\Models\User;
use App\Facades\Branch;
use App\Services\ConsumptionRequestService;
use App\Notifications\NuevaSolicitudConsumoNotification;
use App\Notifications\SolicitudConsumoCanceladaNotification;
use App\Notifications\SolicitudConsumoModificadaNotification;
use App\Events\NuevaNotificacion;
use App\Exports\ConsumptionRequestsGeneralExport;
use App\Exports\ConsumptionRequestSingleExport;
use App\Facades\CompanyFacade;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Exception;

class ConsumptionRequestController extends Controller
{
    public function __construct(private ConsumptionRequestService $consumptionRequestService) {}

    /**
     * Display a listing of consumption requests.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $area = $request->get('area');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $order = $request->get('order', 'desc');
        $perPageInput = (int) $request->input('per_page', 10);
        $perPage = in_array($perPageInput, [10, 25, 50, 100], true) ? $perPageInput : 10;

        $user = $request->user();
        $isAdmin = $this->checkIsAdmin($user);

        $query = ConsumptionRequest::query()
            ->with([
                'warehouse', 
                'user', 
                'details.product.stocks',
                'details.product.unitOfMeasure',
                'details.product.categories'
            ])
            ->orderBy('number', $order);

        // Si es consumidor, solo ve sus propias solicitudes
        if ($user && $user->hasRole(['Consumidor', 'consumidor']) && !$isAdmin) {
            $query->where('user_id', $user->id);
        }

        // Aplicar filtros
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('requested_by', 'ilike', "%{$search}%")
                  ->orWhere('notes', 'ilike', "%{$search}%");
                  
                // Validar si el search es un número entero
                if (is_numeric($search)) {
                    $q->orWhere('number', (int)$search);
                }
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($area) {
            $query->where('requested_by', $area);
        }

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $requests = $query->paginate($perPage)->withQueryString();

        $defaultAreas = ['Cocina', 'Pastelería', 'Panadería', 'Eventos', 'Producción', 'Despacho'];
        $dbAreas = ConsumptionRequest::query()
            ->whereNotNull('requested_by')
            ->where('requested_by', '!=', '')
            ->distinct()
            ->pluck('requested_by')
            ->all();
        $areas = array_values(array_unique(array_merge($defaultAreas, $dbAreas)));
        sort($areas);

        return Inertia::render('Admin/ConsumptionRequest/Index', [
            'requests' => ConsumptionRequestResource::collection($requests),
            'areas' => $areas,
            'isAdmin' => $isAdmin,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'area' => $area,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'order' => $order,
                'per_page' => $perPage,
            ]
        ]);
    }

    /**
     * Show the POS interface adapted for consumption request.
     */
    public function create(Request $request): Response
    {
        $user = $request->user();
        if ($user && $user->hasRole(['Almacén', 'almacen'])) {
            abort(403, 'El personal de almacén no tiene permitido crear solicitudes de consumo.');
        }

        $branchId = Branch::getActiveBranchId();
        
        $initialConfig = [
            'activeWarehouseId' => Branch::getActiveWarehouseId(),
            'activePosId' => null,
            'warehouseName' => Branch::getActiveWarehouse()?->name ?? 'ALMACÉN CENTRAL',
            'posName' => 'Consumo',
            'isFixedDiscount' => false,
            'operationType' => 'consumption',
            'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
        ];

        $warehouses = [];
        if ($branchId) {
            $warehouses = Warehouse::where('branch_id', $branchId)
                ->select('id', 'name')
                ->where('is_active', true)
                ->get();
        }

        return Inertia::render('Admin/POS/Index', [
            'initialConfig' => $initialConfig,
            'pointsOfSale' => [],
            'warehouses' => $warehouses,
            'shippingHistory' => ['companies' => [], 'origins' => [], 'destinations' => []],
            'initialQuotation' => null,
        ]);
    }

    /**
     * Store a new consumption request.
     * Agrupa los ítems del carrito por warehouse_id y crea una solicitud por cada almacén.
     */
    public function store(SaveConsumptionRequest $request): RedirectResponse
    {
        $user = $request->user();
        if ($user && $user->hasRole(['Almacén', 'almacen'])) {
            abort(403, 'El personal de almacén no tiene permitido registrar solicitudes de consumo.');
        }

        try {
            $data = $request->validated();

            $isAdmin = $user && ($user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador', 'Super Admin']));

            // Si es administrador, toma el área enviada en la solicitud o su área de usuario
            // Si es consumidor/estándar, se toma estrictamente el área de su perfil asignado
            $requestedBy = $isAdmin
                ? ($data['requested_by'] ?? $user?->area)
                : ($user?->area ?? $data['requested_by'] ?? null);

            if (empty($requestedBy)) {
                $errorMessage = $isAdmin
                    ? 'Debe seleccionar un área operativa (Cocina, Pastelería, Panadería, Eventos, Producción, Despacho) para registrar la solicitud.'
                    : 'Tu usuario no tiene un área operativa asignada (Cocina, Pastelería, Panadería, Eventos, Producción, Despacho) para registrar consumos.';

                return redirect()->back()->withErrors([
                    'error' => $errorMessage
                ]);
            }

            // Agrupar los ítems del carrito por warehouse_id
            $itemsByWarehouse = collect($data['cart'])->groupBy('warehouse_id');

            $createdRequests = [];

            foreach ($itemsByWarehouse as $warehouseId => $warehouseItems) {
                $requestData = [
                    'warehouse_id'  => $warehouseId,
                    'requested_by'  => $requestedBy,
                    'notes'         => $data['notes'] ?? null,
                    'date'          => $data['date'] ?? now()->toDateString(),
                ];

                $consumptionRequest = $this->consumptionRequestService->createRequest(
                    $requestData,
                    $warehouseItems->toArray()
                );

                // Disparar evento de tiempo real (silencioso si Reverb no está activo)
                try {
                    event(new \App\Events\ConsumptionRequestCreated($consumptionRequest));
                } catch (\Throwable) {
                    // Reverb no disponible — no interrumpir el flujo
                }

                // Obtener destinatarios elegibles: super admin o (Almacén/Admin de la sucursal), activos, sin Consumidor
                $branchId = $consumptionRequest->warehouse->branch_id;
                $notificationMessage = "Se ha registrado la solicitud de consumo #{$consumptionRequest->formatted_number} por el área de {$consumptionRequest->requested_by}.";

                $recipients = $this->recipientsForNewConsumptionRequest($branchId, $request->user()->id);

                // Registrar notificación persistente en BD + transmitir por socket
                foreach ($recipients as $recipient) {
                    $recipient->notify(new NuevaSolicitudConsumoNotification($consumptionRequest));

                    try {
                        NuevaNotificacion::dispatch(
                            (string)$recipient->id,
                            $notificationMessage,
                            'new_consumption_request'
                        );
                    } catch (\Throwable) {
                        // Reverb no disponible — la notificación de BD ya fue guardada
                    }
                }

                $createdRequests[] = $consumptionRequest;
            }

            // Si solo se creó una solicitud, abrir PDF directamente
            $firstRequest = $createdRequests[0];

            $totalCreated = count($createdRequests);
            $numbers = implode(', #', array_map(fn($r) => $r->number, $createdRequests));

            return redirect()->route('admin.consumption-requests.create')->with([
                'success' => $totalCreated === 1
                    ? "Solicitud de consumo #{$firstRequest->number} de {$firstRequest->requested_by} registrada exitosamente."
                    : "Se crearon {$totalCreated} solicitudes de consumo (#{$numbers}) de {$firstRequest->requested_by}, una por cada almacén.",
                'success_data' => [
                    'id' => $firstRequest->id,
                ],
            ]);
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Error al guardar la solicitud de consumo: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the edit interface for a pending consumption request.
     */
    public function edit(ConsumptionRequest $consumptionRequest): Response
    {
        $user = auth()->user();

        // Validaciones estrictas: solo estado pendiente, sin aprobar y propietario (o super admin)
        if ($consumptionRequest->status !== 'pendiente' || !is_null($consumptionRequest->approved_at)) {
            abort(403, 'Solo se pueden editar solicitudes en estado pendiente de aprobación.');
        }

        if ($consumptionRequest->user_id !== $user?->id && !$user?->is_super_admin) {
            abort(403, 'Solo el creador de la solicitud puede editarla.');
        }

        $consumptionRequest->load([
            'warehouse',
            'details.product.stocks' => function($q) use ($consumptionRequest) {
                $q->where('warehouse_id', $consumptionRequest->warehouse_id);
            },
            'details.product.unitOfMeasure',
            'details.product.categories'
        ]);

        $branchId = $consumptionRequest->warehouse?->branch_id ?? Branch::getActiveBranchId();

        $initialConfig = [
            'activeWarehouseId' => $consumptionRequest->warehouse_id,
            'activePosId' => null,
            'warehouseName' => $consumptionRequest->warehouse?->name ?? 'ALMACÉN CENTRAL',
            'posName' => 'Consumo',
            'isFixedDiscount' => false,
            'operationType' => 'consumption',
            'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
        ];

        $warehouses = Warehouse::where('branch_id', $branchId)
            ->select('id', 'name')
            ->where('is_active', true)
            ->get();

        // Formatear items de la solicitud para el carrito
        $cartItems = $consumptionRequest->details->map(function ($detail) use ($consumptionRequest) {
            $product = $detail->product;
            $stock = $product?->stocks?->first();
            $physicalStock = (float)($stock?->quantity ?? 0);
            $reservedStock = (float)($product?->reserved_quantity ?? 0);
            $availableStock = max(0, $physicalStock - $reservedStock);

            return [
                'id' => $detail->product_id,
                'name' => $product?->name ?? 'Producto',
                'code' => $product?->code ?? '',
                'quantity' => (float)$detail->quantity_requested,
                'price' => (float)($product?->price ?? 0),
                'discount' => 0,
                'stock' => $availableStock,
                'stocks' => $product?->stocks ? $product->stocks->map(fn($s) => [
                    'warehouse_id' => $s->warehouse_id,
                    'quantity' => (float)$s->quantity,
                ])->toArray() : [],
                'is_stock_exceeded' => false,
                'warehouse_id' => $consumptionRequest->warehouse_id,
                'warehouse_name' => $consumptionRequest->warehouse?->name ?? '',
                'unit_of_measure' => $product?->unitOfMeasure?->name ?? 'UND',
                'type' => $product?->type ?? 'material',
                'is_inventoriable' => (bool)($product?->is_inventoriable ?? true),
                'image_path' => $product?->image_path,
            ];
        })->values()->toArray();

        $editingRequest = [
            'id' => $consumptionRequest->id,
            'number' => $consumptionRequest->number,
            'formatted_number' => $consumptionRequest->formatted_number,
            'notes' => $consumptionRequest->notes ?? '',
            'requested_by' => $consumptionRequest->requested_by,
            'items' => $cartItems,
        ];

        return Inertia::render('Admin/POS/Index', [
            'initialConfig' => $initialConfig,
            'pointsOfSale' => [],
            'warehouses' => $warehouses,
            'shippingHistory' => ['companies' => [], 'origins' => [], 'destinations' => []],
            'initialQuotation' => null,
            'editingRequest' => $editingRequest,
        ]);
    }

    /**
     * Update an existing pending consumption request.
     */
    public function update(SaveConsumptionRequest $request, ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        $user = $request->user();

        if ($consumptionRequest->status !== 'pendiente' || !is_null($consumptionRequest->approved_at)) {
            return redirect()->back()->withErrors([
                'error' => 'Solo se pueden modificar solicitudes en estado pendiente de aprobación.'
            ]);
        }

        if ($consumptionRequest->user_id !== $user?->id && !$user?->is_super_admin) {
            return redirect()->back()->withErrors([
                'error' => 'Solo el creador de la solicitud puede modificarla.'
            ]);
        }

        try {
            $data = $request->validated();
            $items = $data['cart'] ?? [];

            $isAdmin = $user && ($user->is_super_admin || $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador', 'Super Admin']));
            if (!$isAdmin && !empty($user?->area)) {
                $data['requested_by'] = $user->area;
            }

            $updated = $this->consumptionRequestService->updateRequest($consumptionRequest, $data, $items);

            // Disparar evento de actualización en tiempo real por socket
            try {
                event(new \App\Events\ConsumptionRequestUpdated($updated, 'updated'));
            } catch (\Throwable) {
                // Silencioso si Reverb no está disponible
            }

            return redirect()->route('admin.consumption-requests.show', $updated->id)->with([
                'success' => "Solicitud de consumo #{$updated->number} actualizada exitosamente."
            ]);
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Error al actualizar la solicitud: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the detail of a consumption request.
     */
    public function show(ConsumptionRequest $consumptionRequest): Response
    {
        $consumptionRequest->load([
            'warehouse',
            'user',
            'receivedByUser',
            'dispatchedByUser',
            'approvedByUser',
            'observedByUser',
            'cancelledByUser',
            'details.product.stocks' => function($q) use ($consumptionRequest) {
                $q->where('warehouse_id', $consumptionRequest->warehouse_id);
            },
            'details.product.unitOfMeasure',
            'details.product.categories'
        ]);

        $user = auth()->user();
        
        // Si es consumidor, validar que la solicitud sea suya
        if ($user && $user->hasRole(['Consumidor', 'consumidor']) && !$user->is_super_admin && !$user->hasRole(['Admin', 'admin', 'Administrador', 'administrador'])) {
            if ($consumptionRequest->user_id !== $user->id) {
                abort(403, 'No tienes permiso para ver esta solicitud de consumo.');
            }
        }

        $isAdmin = $user ? ($user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin) : false;

        return Inertia::render('Admin/ConsumptionRequest/Show', [
            'consumptionRequest' => new ConsumptionRequestResource($consumptionRequest),
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Dispatch the stock for a consumption request.
     */
    public function dispatchRequest(Request $request, ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        $user = auth()->user();
        $canDispatch = $user ? $user->hasRole(['Almacén', 'almacen']) : false;
        if (!$canDispatch) {
            return redirect()->back()->withErrors([
                'error' => 'No tienes permiso para despachar esta solicitud de consumo.'
            ]);
        }

        $request->validate([
            'quantities' => 'required|array',
            'quantities.*' => 'required|numeric|min:0',
            'observations' => 'nullable|array',
            'observations.*' => 'nullable|string',
            'dispatch_observation' => 'nullable|string|max:500',
        ]);

        try {
            $consumptionRequest->load(['details.product']);
            $updatedRequest = $this->consumptionRequestService->dispatchRequest(
                $consumptionRequest,
                $request->input('quantities'),
                $request->input('observations', []),
                $request->input('dispatch_observation')
            );

            // Notificar al creador que su solicitud fue despachada
            $creator = $updatedRequest->user;
            if ($creator && $creator->is_active) {
                $notification = new \App\Notifications\SolicitudConsumoDespachadaNotification($updatedRequest);
                $creator->notify($notification);

                try {
                    \App\Events\NuevaNotificacion::dispatch(
                        (string)$creator->id,
                        "Tu solicitud de consumo #{$updatedRequest->formatted_number} del área de {$updatedRequest->requested_by} ha sido despachada por almacén y está lista para recepcionar.",
                        'consumption_request_dispatched'
                    );
                } catch (\Throwable) {
                    // Reverb no disponible — no interrumpir el flujo
                }
            }

            // Socket global de sucursal para actualizar la tabla del listado reactivamente
            try {
                event(new \App\Events\ConsumptionRequestUpdated($updatedRequest, 'dispatched'));
            } catch (\Throwable) {
                // Reverb no disponible — no interrumpir el flujo
            }

            $msg = $updatedRequest->status === 'entregado'
                ? 'Todos los productos fueron despachados exitosamente.'
                : 'Se realizó un despacho parcial del stock disponible. Los productos restantes siguen pendientes.';

            return redirect()->back()->with('flash', [
                'success' => $msg
            ]);
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Error en el despacho: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generate query string for the Purchase Order creation view.
     */
    public function generatePurchaseOrder(ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        try {
            $consumptionRequest->load(['details.product']);
            $missingItems = $this->consumptionRequestService->getMissingItems($consumptionRequest);

            if (empty($missingItems)) {
                return redirect()->back()->withErrors([
                    'error' => 'No existen faltantes en esta solicitud de consumo.'
                ]);
            }

            // Redirigir a la vista de creación de solicitudes de compra con los datos del faltante precargados
            return redirect()->route('admin.purchase-orders.create', [
                'from_consumption_id' => $consumptionRequest->id,
                'warehouse_id' => $consumptionRequest->warehouse_id,
            ]);
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Error al generar la orden de compra: ' . $e->getMessage()
            ]);
        }
    }

    public function cancel(Request $request, ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;
        if (!$isAdmin) {
            return back()->withErrors(['error' => 'Solo los administradores pueden cancelar las solicitudes de consumo.']);
        }

        $request->validate([
            'cancellation_notes' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cancellation_notes.required' => 'El motivo de la cancelación es obligatorio.',
            'cancellation_notes.min' => 'El motivo de la cancelación debe tener al menos 5 caracteres.',
        ]);

        try {
            $notes = (string) $request->input('cancellation_notes');
            $this->consumptionRequestService->cancelRequest($consumptionRequest, $notes);

            $consumptionRequest->loadMissing(['warehouse.branch', 'user']);

            // 1. Notificar al creador de la solicitud
            $creator = $consumptionRequest->user;
            if ($creator && $creator->is_active) {
                $notification = new SolicitudConsumoCanceladaNotification($consumptionRequest, $notes);
                $creator->notify($notification);

                try {
                    NuevaNotificacion::dispatch(
                        (string) $creator->id,
                        $notification->toArray($creator)['message'],
                        'consumption_request_cancelled'
                    );
                } catch (\Throwable) {
                    // Silencioso si Reverb no está disponible
                }
            }

            // 2. Notificar al personal de Almacén de la sucursal
            $branchId = $consumptionRequest->warehouse->branch_id;
            $warehouseUsers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['Almacén', 'almacen', 'Almacen', 'almacén']);
            })
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

            $wNotification = new SolicitudConsumoCanceladaNotification($consumptionRequest, $notes);
            foreach ($warehouseUsers as $wUser) {
                if ($creator && $wUser->id === $creator->id) {
                    continue; // Evitar duplicados
                }
                $wUser->notify($wNotification);

                try {
                    NuevaNotificacion::dispatch(
                        (string) $wUser->id,
                        $wNotification->toArray($wUser)['message'],
                        'consumption_request_cancelled'
                    );
                } catch (\Throwable) {
                    // Silencioso si Reverb no está disponible
                }
            }

            // Disparar evento de actualización en tiempo real por socket en sucursal
            try {
                event(new \App\Events\ConsumptionRequestUpdated($consumptionRequest, 'cancelled'));
            } catch (\Throwable) {
                // Silencioso si Reverb no está disponible
            }

            return redirect()->back()->with('success', 'Solicitud cancelada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function updateDetailQuantity(
        Request $request,
        ConsumptionRequest $consumptionRequest,
        ConsumptionRequestDetail $detail
    ): RedirectResponse {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;
        if (!$isAdmin) {
            return back()->withErrors(['error' => 'Solo los administradores pueden modificar las cantidades de las solicitudes.']);
        }

        $request->validate([
            'quantity_requested' => ['required', 'numeric', 'min:0.01'],
            'modification_notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity_requested.required' => 'La cantidad solicitada es obligatoria.',
            'quantity_requested.min' => 'La cantidad solicitada debe ser mayor a 0.',
        ]);

        try {
            $oldQty = (float) $detail->quantity_requested;
            $newQty = (float) $request->input('quantity_requested');
            $notes = $request->input('modification_notes');

            $detail->loadMissing(['product.unitOfMeasure']);
            $productName = $detail->product?->name ?? 'Producto';
            $unitName = $detail->product?->unitOfMeasure?->name ?? 'UND';

            $this->consumptionRequestService->updateDetailQuantity(
                $consumptionRequest,
                $detail,
                $newQty,
                $notes
            );

            $consumptionRequest->loadMissing(['warehouse.branch', 'user']);

            // 1. Notificar al creador
            $creator = $consumptionRequest->user;
            if ($creator && $creator->is_active) {
                $notification = new SolicitudConsumoModificadaNotification(
                    $consumptionRequest,
                    $productName,
                    $oldQty,
                    $newQty,
                    $unitName,
                    $notes
                );
                $creator->notify($notification);

                try {
                    NuevaNotificacion::dispatch(
                        (string) $creator->id,
                        $notification->toArray($creator)['message'],
                        'consumption_request_modified'
                    );
                } catch (\Throwable) {
                    // Silencioso si Reverb no está disponible
                }
            }

            // 2. Notificar al personal de Almacén
            $branchId = $consumptionRequest->warehouse->branch_id;
            $warehouseUsers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['Almacén', 'almacen', 'Almacen', 'almacén']);
            })
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

            $wNotification = new SolicitudConsumoModificadaNotification(
                $consumptionRequest,
                $productName,
                $oldQty,
                $newQty,
                $unitName,
                $notes
            );

            foreach ($warehouseUsers as $wUser) {
                if ($creator && $wUser->id === $creator->id) {
                    continue;
                }
                $wUser->notify($wNotification);

                try {
                    NuevaNotificacion::dispatch(
                        (string) $wUser->id,
                        $wNotification->toArray($wUser)['message'],
                        'consumption_request_modified'
                    );
                } catch (\Throwable) {
                    // Silencioso si Reverb no está disponible
                }
            }

            // Disparar evento de socket en sucursal
            try {
                event(new \App\Events\ConsumptionRequestUpdated($consumptionRequest, 'item_updated'));
            } catch (\Throwable) {
                // Silencioso si Reverb no está disponible
            }

            return redirect()->back()->with('success', "Cantidad del producto '{$productName}' actualizada correctamente a {$newQty} {$unitName}.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function receive(Request $request, ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        try {
            $receivedQuantities = $request->input('received_quantities', []);
            $observations = $request->input('observations', []);
            $receiveObservations = $request->input('receive_observations', []);
            
            $updatedRequest = $this->consumptionRequestService->receiveRequest($consumptionRequest, $receivedQuantities, $observations, $receiveObservations);

            // Cargar relaciones necesarias
            $updatedRequest->loadMissing(['warehouse', 'receivedByUser', 'details.product.unitOfMeasure']);

            // Crear la instancia de la notificación
            $notification = new \App\Notifications\SolicitudConsumoRecepcionadaNotification($updatedRequest);
            $message = $notification->getMessage();

            // Notificar a Admin/Administrador de la sucursal + super admin (deduplicados, activos)
            $branchId = $updatedRequest->warehouse->branch_id;
            $adminUsers = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['Admin', 'admin', 'Administrador', 'administrador']);
            })
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

            // Agregar super admins activos
            $superAdminIds = User::where('is_super_admin', true)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            $recipientIds = array_unique(array_merge($adminUsers, $superAdminIds));

            $notification = new \App\Notifications\SolicitudConsumoRecepcionadaNotification($updatedRequest);
            $message = $notification->getMessage();

            foreach ($recipientIds as $recipientId) {
                $recipient = User::find($recipientId);
                if (!$recipient) continue;

                $recipient->notify($notification);

                try {
                    \App\Events\NuevaNotificacion::dispatch(
                        (string)$recipientId,
                        $message,
                        'consumption_request_received'
                    );
                } catch (\Throwable) {
                    // Reverb no disponible
                }
            }

            // Socket global de sucursal para actualizar la tabla del listado reactivamente
            try {
                event(new \App\Events\ConsumptionRequestUpdated($updatedRequest, 'received'));
            } catch (\Throwable) {
                // Reverb no disponible
            }

            return redirect()->back()->with('success', 'Recepción confirmada correctamente. El ciclo ha finalizado.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Error al confirmar la recepción: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Approve a consumption request.
     */
    public function approve(Request $request, ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;
        if (!$isAdmin) {
            return back()->withErrors(['error' => 'Solo los administradores pueden aprobar las solicitudes de consumo.']);
        }

        try {
            $notes = $request->input('observation_notes');
            $this->consumptionRequestService->approveRequest($consumptionRequest, (string)$user->id, $notes);

            // Notificar al creador que su solicitud fue aprobada
            $creator = $consumptionRequest->user;
            if ($creator && $creator->is_active) {
                $notification = new \App\Notifications\SolicitudConsumoAprobadaNotification($consumptionRequest);
                $creator->notify($notification);

                try {
                    \App\Events\NuevaNotificacion::dispatch(
                        (string)$creator->id,
                        $notification->toArray($creator)['message'],
                        'consumption_request_approved'
                    );
                } catch (\Throwable) {
                    // Reverb no disponible
                }
            }

            // Disparar evento de actualización en tiempo real por socket
            try {
                event(new \App\Events\ConsumptionRequestUpdated($consumptionRequest));
            } catch (\Throwable) {
                // Silencioso si Reverb no está disponible
            }

            return back()->with('success', 'Solicitud de consumo aprobada exitosamente.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al aprobar la solicitud: ' . $e->getMessage()]);
        }
    }

    /**
     * Observe a consumption request.
     */
    public function observe(Request $request, ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;
        if (!$isAdmin) {
            return back()->withErrors(['error' => 'Solo los administradores pueden observar las solicitudes de consumo.']);
        }

        $request->validate([
            'observation_notes' => ['required', 'string', 'min:3'],
        ], [
            'observation_notes.required' => 'Las notas de la observación son obligatorias.',
            'observation_notes.min' => 'La observación debe tener al menos 3 caracteres.',
        ]);

        try {
            $this->consumptionRequestService->observeRequest(
                $consumptionRequest,
                (string)$user->id,
                $request->input('observation_notes')
            );

            // Disparar evento de actualización en tiempo real por socket
            try {
                event(new \App\Events\ConsumptionRequestUpdated($consumptionRequest));
            } catch (\Throwable) {
                // Silencioso si Reverb no está disponible
            }

            return back()->with('success', 'Solicitud observada correctamente.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al registrar la observación: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate and stream the consumption request PDF.
     */
    public function print(\Illuminate\Http\Request $request, ConsumptionRequest $consumptionRequest): \Illuminate\Http\Response
    {
        $company = \App\Facades\CompanyFacade::getCompany();
        $format = $request->get('format', $company?->receipt_type ?? 'media');
        $view = $format === 'rollo' 
            ? 'admin.consumption-requests.receipt-roll' 
            : 'admin.consumption-requests.receipt';

        $consumptionRequest->load([
            'warehouse.branch.company',
            'user',
            'approvedByUser',
            'dispatchedByUser',
            'receivedByUser',
            'observedByUser',
            'cancelledByUser',
            'details.product.unitOfMeasure',
        ]);

        $pdf = Pdf::loadView($view, [
            'request' => $consumptionRequest,
        ]);

        if ($format === 'rollo') {
            $baseHeight = 460;
            $rowHeight = 50;
            $itemsCount = max(1, $consumptionRequest->details->count());
            $dynamicHeight = $baseHeight + ($itemsCount * $rowHeight);
            
            $pdf->setPaper([0, 0, 226, $dynamicHeight], 'portrait');
        } else {
            $pdf->setPaper('letter', 'portrait');
        }

        return $pdf->stream("Solicitud-Consumo-{$consumptionRequest->formatted_number}.pdf");
    }

    /**
     * Export general report of consumption requests in Excel (.xlsx).
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->ensureAdminAccess($request->user());

        $search = $request->get('search');
        $status = $request->get('status');
        $area = $request->get('area');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $filename = 'Reporte-General-Solicitudes-Consumo-' . now()->format('Y-m-d-His') . '.xlsx';

        return Excel::download(
            new ConsumptionRequestsGeneralExport($search, $status, $area, $startDate, $endDate),
            $filename
        );
    }

    private const MAX_PDF_BATCH_RECORDS = 5000;
    private const PDF_CHUNK_SIZE = 500;
    private const PDF_RENDER_BATCH_SIZE = 30;

    /**
     * Export general report of consumption requests in PDF (processed in batches, max 5,000 records).
     */
    public function exportPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $this->ensureAdminAccess($request->user());

        $search = $request->get('search');
        $status = $request->get('status');
        $area = $request->get('area');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $baseQuery = ConsumptionRequestDetail::query()
            ->whereHas('consumptionRequest', function ($q) use ($search, $status, $area, $startDate, $endDate): void {
                if ($search) {
                    $q->where(function ($sub) use ($search): void {
                        $sub->where('requested_by', 'ilike', "%{$search}%")
                            ->orWhere('notes', 'ilike', "%{$search}%");
                        if (is_numeric($search)) {
                            $sub->orWhere('number', (int) $search);
                        }
                    });
                }

                if ($status) {
                    $q->where('status', $status);
                }

                if ($area) {
                    $q->where('requested_by', $area);
                }

                if ($startDate) {
                    $q->whereDate('date', '>=', $startDate);
                }

                if ($endDate) {
                    $q->whereDate('date', '<=', $endDate);
                }
            });

        $totalCount = (clone $baseQuery)->count();
        $maxLimit = self::MAX_PDF_BATCH_RECORDS;

        if ($request->boolean('check_only')) {
            if ($totalCount > $maxLimit) {
                return response()->json([
                    'allowed' => false,
                    'total' => $totalCount,
                    'max' => $maxLimit,
                    'message' => "El reporte PDF General procesa como máximo " . number_format($maxLimit) . " registros por lote (se encontraron " . number_format($totalCount) . " registros). Por favor, filtre por Área Solicitante, Estado o Rango de Fechas solo lo necesario.",
                ], 422);
            }

            return response()->json([
                'allowed' => true,
                'total' => $totalCount,
                'max' => $maxLimit,
            ]);
        }

        if ($totalCount > $maxLimit) {
            $limitMessage = "El reporte PDF General procesa como máximo " . number_format($maxLimit) . " registros por lote (se encontraron " . number_format($totalCount) . " registros). Por favor, filtre por Área Solicitante, Estado o Rango de Fechas solo lo necesario.";

            if ($request->expectsJson()) {
                return response()->json([
                    'allowed' => false,
                    'total' => $totalCount,
                    'max' => $maxLimit,
                    'message' => $limitMessage,
                ], 422);
            }

            return redirect()
                ->route('admin.consumption-requests.index', array_filter([
                    'search' => $search,
                    'status' => $status,
                    'area' => $area,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]))
                ->withErrors(['error' => $limitMessage]);
        }

        $query = $baseQuery
            ->select([
                'consumption_request_details.id',
                'consumption_request_details.consumption_request_id',
                'consumption_request_details.product_id',
                'consumption_request_details.quantity_requested',
                'consumption_request_details.quantity_delivered',
                'consumption_request_details.created_at',
            ])
            ->addSelect([
                'stock_avg_cost' => \App\Models\Stock::withoutGlobalScopes()
                    ->select('average_cost')
                    ->whereColumn('stocks.product_id', 'consumption_request_details.product_id')
                    ->whereIn('stocks.warehouse_id', function ($sub): void {
                        $sub->select('warehouse_id')
                            ->from('consumption_requests')
                            ->whereColumn('consumption_requests.id', 'consumption_request_details.consumption_request_id');
                    })
                    ->limit(1),
            ])
            ->with([
                'consumptionRequest:id,number,date,created_at,requested_by,user_id,status',
                'consumptionRequest.user:id,name',
                'product:id,code,name,price,unit_of_measure_id',
                'product.unitOfMeasure:id,name,abbreviation',
            ])
            ->orderByDesc('consumption_request_details.created_at')
            ->orderByDesc('consumption_request_details.id');

        $records = [];
        $uniqueRequestIds = [];
        $totalItems = 0;
        $totalRequested = 0.0;
        $totalDelivered = 0.0;
        $totalCost = 0.0;

        $query->chunk(self::PDF_CHUNK_SIZE, function ($batch) use (
            &$records,
            &$uniqueRequestIds,
            &$totalItems,
            &$totalRequested,
            &$totalDelivered,
            &$totalCost
        ): void {
            foreach ($batch as $detail) {
                $req = $detail->consumptionRequest;
                $product = $detail->product;

                $price = (float) ($product?->price ?? 0);
                $unitCost = $price > 0 ? $price : (float) ($detail->stock_avg_cost ?? 0);
                $qtyReq = (float) ($detail->quantity_requested ?? 0);
                $qtyDel = (float) ($detail->quantity_delivered ?? 0);

                if ($detail->consumption_request_id) {
                    $uniqueRequestIds[$detail->consumption_request_id] = true;
                }
                $totalItems++;
                $totalRequested += $qtyReq;
                $totalDelivered += $qtyDel;
                $totalCost += $unitCost;

                $records[] = (object) [
                    'formatted_number' => $req?->formatted_number ?? 'S/N',
                    'date_formatted' => $this->formatSpanishDate($req?->date ?? $req?->created_at),
                    'time_formatted' => $req?->created_at ? $req->created_at->format('H:i') . ' hrs' : '',
                    'requested_by' => $req?->requested_by ?? 'Sin área',
                    'user_name' => $req?->user?->name ?? 'N/A',
                    'status' => (string) ($req?->status ?? 'pendiente'),
                    'product_code' => $product?->code ?? 'S/C',
                    'product_name' => $product?->name ?? 'Producto eliminado',
                    'unit_name' => $product?->unitOfMeasure?->abbreviation ?? $product?->unitOfMeasure?->name ?? 'UND',
                    'quantity_requested' => $qtyReq,
                    'quantity_delivered' => $qtyDel,
                    'unit_cost' => $unitCost,
                ];
            }

            unset($batch);
            gc_collect_cycles();
        });

        $company = CompanyFacade::getCompany();

        $summary = [
            'total_requests' => count($uniqueRequestIds),
            'total_items' => $totalItems,
            'total_requested' => $totalRequested,
            'total_delivered' => $totalDelivered,
            'total_cost' => $totalCost,
        ];

        $filters = [
            'search' => $search,
            'status' => $status,
            'area' => $area,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        $logoBase64 = null;
        $logoPath = public_path('img/logo-light.png');
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($logoData);
            unset($logoData);
        }

        $now = now();
        $generatedDateText = $this->formatSpanishDate($now);
        $generatedTimeText = $now->format('H:i') . ' hrs';
        $generatedByUser = $request->user()?->name ?? 'Administrador';

        $periodText = null;
        if (!empty($startDate) || !empty($endDate)) {
            $startFormatted = !empty($startDate) ? $this->formatSpanishDate($startDate) : 'Inicio';
            $endFormatted = !empty($endDate) ? $this->formatSpanishDate($endDate) : 'Actualidad';
            $periodText = "{$startFormatted} al {$endFormatted}";
        }

        $batches = !empty($records)
            ? array_chunk($records, self::PDF_RENDER_BATCH_SIZE)
            : [[]];
        unset($records, $uniqueRequestIds);
        gc_collect_cycles();

        $masterDompdf = new \Dompdf\Dompdf();
        $masterDompdf->setPaper('a4', 'landscape');
        $sharedCanvas = new \Dompdf\Adapter\CPDF('a4', 'landscape', $masterDompdf);
        $sharedFontMetrics = $masterDompdf->getFontMetrics();
        $sharedFontMetrics->setCanvas($sharedCanvas);

        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batchRecords) {
            $isFirstBatch = ($batchIndex === 0);
            $isLastBatch = ($batchIndex === $totalBatches - 1);

            $dompdf = new \Dompdf\Dompdf();
            if (!$isFirstBatch) {
                $sharedCanvas->new_page();
            }
            $dompdf->setCanvas($sharedCanvas);
            $dompdf->setFontMetrics($sharedFontMetrics);

            $html = view('exports.reports.consumption-requests-general-pdf', [
                'records' => $batchRecords,
                'summary' => $summary,
                'company' => $company,
                'filters' => $filters,
                'logoBase64' => $isFirstBatch ? $logoBase64 : null,
                'generatedDateText' => $generatedDateText,
                'generatedTimeText' => $generatedTimeText,
                'generatedByUser' => $generatedByUser,
                'periodText' => $periodText,
                'isFirstBatch' => $isFirstBatch,
                'isLastBatch' => $isLastBatch,
                'batchNumber' => $batchIndex + 1,
                'totalBatches' => $totalBatches,
            ])->render();

            $dompdf->loadHtml($html);
            $dompdf->render();

            if ($dompdf->getTree()) {
                foreach ($dompdf->getTree() as $frame) {
                    $decorator = $frame->get_decorator();
                    if ($decorator) {
                        $decorator->dispose(false);
                    }
                    $frame->dispose(false);
                }
            }

            unset($dompdf, $html, $batchRecords);
            if (($batchIndex + 1) % 20 === 0 || $isLastBatch) {
                gc_collect_cycles();
            }
        }

        unset($batches, $logoBase64, $masterDompdf);
        gc_collect_cycles();

        $filename = 'Reporte-General-Solicitudes-Consumo-' . $now->format('Y-m-d-His') . '.pdf';
        $pdfContent = $sharedCanvas->output();

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function formatSpanishDate(mixed $dateVal): string
    {
        if (!$dateVal) {
            return '-';
        }

        $d = $dateVal instanceof \Carbon\CarbonInterface
            ? $dateVal
            : \Carbon\Carbon::parse($dateVal);

        $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $days[(int) $d->format('w')] . ', ' . $d->format('d') . ' de ' . $months[(int) $d->format('n')] . ' de ' . $d->format('Y');
    }

    /**
     * Export specific consumption request in Excel (.xlsx).
     */
    public function exportSingleExcel(Request $request, ConsumptionRequest $consumption_request): BinaryFileResponse
    {
        $this->ensureAdminAccess($request->user());

        $consumption_request->load([
            'warehouse',
            'user',
            'details.product.unitOfMeasure',
            'details.product.stocks',
        ]);

        $filename = 'Solicitud-Consumo-' . $consumption_request->formatted_number . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ConsumptionRequestSingleExport($consumption_request),
            $filename
        );
    }

    /**
     * Export specific consumption request in PDF.
     */
    public function exportSinglePdf(Request $request, ConsumptionRequest $consumption_request): \Illuminate\Http\Response
    {
        $this->ensureAdminAccess($request->user());

        $consumption_request->load([
            'warehouse.branch.company',
            'user',
            'approvedByUser',
            'dispatchedByUser',
            'receivedByUser',
            'details.product.unitOfMeasure',
            'details.product.stocks',
        ]);

        $company = CompanyFacade::getCompany();

        $pdf = Pdf::loadView('exports.reports.consumption-request-single-pdf', [
            'consumptionRequest' => $consumption_request,
            'company' => $company,
        ])->setPaper('a4', 'portrait');

        $filename = 'Solicitud-Consumo-' . $consumption_request->formatted_number . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Check whether the user has ADMIN or Super Admin privileges.
     */
    private function checkIsAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return (bool) $user->is_super_admin || $user->hasAnyRole([
            'Admin',
            'admin',
            'Administrador',
            'administrador',
            'Super-admin',
            'Super Admin',
            'Super Administrador',
            'super administrador',
            'super-admin',
            'super_admin',
            'super admin',
        ]);
    }

    /**
     * Abort with 403 if the user is not an ADMIN or Super Admin.
     */
    private function ensureAdminAccess(?User $user): void
    {
        if (!$this->checkIsAdmin($user)) {
            abort(403, 'Solo los usuarios con rol de Administrador o Super Admin pueden exportar estos reportes.');
        }
    }

    /**
     * Resolve notification recipients for a new consumption request.
     * Super admins always included; branch admins and warehouse users from the same branch; excludes consumers.
     */
    private function recipientsForNewConsumptionRequest(string $branchId, string $creatorId): \Illuminate\Support\Collection
    {
        return User::where(function ($query) use ($branchId) {
                $query->where('is_super_admin', true)
                    ->orWhere(function ($sub) use ($branchId) {
                        $sub->where('branch_id', $branchId)
                            ->whereHas('roles', function ($roleQuery) {
                                $roleQuery->whereIn('name', ['Almacén', 'almacen', 'Almacen', 'almacén', 'Admin', 'admin', 'Administrador', 'administrador']);
                            });
                    });
            })
            ->where('is_active', true)
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['Consumidor', 'consumidor']);
            })
            ->where('id', '!=', $creatorId)
            ->get()
            ->unique('id');
    }
}
