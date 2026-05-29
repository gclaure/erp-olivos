<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveConsumptionRequest;
use App\Http\Resources\ConsumptionRequestResource;
use App\Models\ConsumptionRequest;
use App\Models\Warehouse;
use App\Facades\Branch;
use App\Services\ConsumptionRequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
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
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $order = $request->get('order', 'desc');

        $query = ConsumptionRequest::query()
            ->with(['warehouse', 'user', 'details.product.stocks'])
            ->orderBy('number', $order);

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

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $requests = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/ConsumptionRequest/Index', [
            'requests' => ConsumptionRequestResource::collection($requests),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'order' => $order,
            ]
        ]);
    }

    /**
     * Show the POS interface adapted for consumption request.
     */
    public function create(Request $request): Response
    {
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
     */
    public function store(SaveConsumptionRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $consumptionRequest = $this->consumptionRequestService->createRequest($data, $data['cart']);

            return redirect()->route('admin.consumption-requests.index')->with([
                'success' => "Solicitud de consumo de {$consumptionRequest->requested_by} registrada exitosamente.",
                'success_data' => [
                    'id' => $consumptionRequest->id,
                ],
            ]);
        } catch (Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Error al guardar la solicitud de consumo: ' . $e->getMessage()
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
            'details.product.stocks' => function($q) use ($consumptionRequest) {
                $q->where('warehouse_id', $consumptionRequest->warehouse_id);
            },
            'details.product.unitOfMeasure'
        ]);

        $user = auth()->user();
        $isAdmin = $user ? ($user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin) : false;

        return Inertia::render('Admin/ConsumptionRequest/Show', [
            'consumptionRequest' => new ConsumptionRequestResource($consumptionRequest),
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Dispatch the stock for a consumption request.
     */
    public function dispatchRequest(ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        try {
            $consumptionRequest->load(['details.product']);
            $updatedRequest = $this->consumptionRequestService->dispatchRequest($consumptionRequest);

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

    /**
     * Cancel a consumption request.
     */
    public function cancel(ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        try {
            $this->consumptionRequestService->cancelRequest($consumptionRequest);
            return redirect()->back()->with('success', 'Solicitud cancelada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $request, ConsumptionRequest $consumptionRequest): RedirectResponse
    {
        try {
            $receivedQuantities = $request->input('received_quantities', []);
            $observations = $request->input('observations', []);
            $this->consumptionRequestService->receiveRequest($consumptionRequest, $receivedQuantities, $observations);
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

            return back()->with('success', 'Solicitud observada correctamente.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al registrar la observación: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate and stream the consumption request PDF.
     */
    public function print(ConsumptionRequest $consumptionRequest): \Illuminate\Http\Response
    {
        $consumptionRequest->load([
            'warehouse.branch.company',
            'user',
            'details.product.unitOfMeasure'
        ]);

        $pdf = Pdf::loadView('admin.consumption-requests.receipt', [
            'request' => $consumptionRequest,
        ]);

        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Solicitud-Consumo-{$consumptionRequest->formatted_number}.pdf");
    }
}
