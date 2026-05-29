<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SavePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrderResource;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $user = auth()->user();
        if (!$user) {
            abort(403, 'Usuario no autenticado.');
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;

        $query = PurchaseOrder::query()
            ->with(['provider', 'warehouse', 'user'])
            // Si no es admin, el trait BranchScoped ya filtra las órdenes por almacenes de su sucursal activa.
            // Pero como capa extra de seguridad, si no es admin podemos limitar a su sucursal explícitamente:
            ->when(!$isAdmin, function ($q) use ($user) {
                $q->whereHas('warehouse', fn ($sq) => $sq->where('branch_id', $user->branch_id));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('provider', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"))
                      ->orWhere('notes', 'ilike', "%{$search}%");
                });
            })
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($dateFrom, fn ($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('date', '<=', $dateTo))
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/PurchaseOrder/Index', [
            'records' => PurchaseOrderResource::collection($orders),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;

        $warehouses = Warehouse::query()
            ->when(!$isAdmin, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $providers = Provider::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $preloadedItems = [];
        $fromConsumptionIds = [];
        $defaultWarehouseId = '';

        $ids = [];
        if ($request->filled('consumption_request_ids')) {
            $consumptionRequestIds = $request->input('consumption_request_ids');
            if (is_string($consumptionRequestIds)) {
                $ids = array_filter(explode(',', $consumptionRequestIds));
            }
        } elseif ($request->filled('from_consumption_id')) {
            $ids = [(string) $request->input('from_consumption_id')];
        }

        if (!empty($ids)) {
            $consumptionRequests = \App\Models\ConsumptionRequest::with(['details.product.unitOfMeasure', 'details.product.stocks.warehouse'])
                ->whereIn('id', $ids)
                ->get();

            $consumptionRequestService = app(\App\Services\ConsumptionRequestService::class);
            $groupedItems = [];
            foreach ($consumptionRequests as $cr) {
                if (empty($defaultWarehouseId)) {
                    $defaultWarehouseId = (string) $cr->warehouse_id;
                }
                $missingItems = $consumptionRequestService->getMissingItems($cr);
                foreach ($missingItems as $item) {
                    $productId = (string) $item['product_id'];
                    $crDetail = $cr->details->where('product_id', $productId)->first();
                    $product = $crDetail?->product;
                    $unit = $product?->unitOfMeasure?->abbreviation ?? 'UND';
                    $packageName = $product?->package_name ?? '';
                    $unitsPerPackage = (float) ($product?->units_per_package ?? 1);
                    $warehousesList = $product?->stocks->map(fn($s) => $s->warehouse->name ?? null)->filter()->unique()->values()->all() ?? [];

                    if (isset($groupedItems[$productId])) {
                        $groupedItems[$productId]['quantity'] += (float) $item['quantity'];
                    } else {
                        $groupedItems[$productId] = [
                            'product_id' => $productId,
                            'product_name' => (string) $item['product_name'],
                            'product_code' => (string) $item['product_code'],
                            'quantity' => (float) $item['quantity'],
                            'unit_price' => (float) $item['unit_price'],
                            'unit' => $unit,
                            'package_name' => $packageName,
                            'units_per_package' => $unitsPerPackage,
                            'warehouses' => $warehousesList,
                        ];
                    }
                }
                $fromConsumptionIds[] = (string) $cr->id;
            }
            $preloadedItems = array_values($groupedItems);
        }

        return Inertia::render('Admin/PurchaseOrder/Create', [
            'warehouses' => $warehouses,
            'providers' => $providers,
            'preloadedItems' => $preloadedItems,
            'fromConsumptionIds' => $fromConsumptionIds,
            'defaultWarehouseId' => $defaultWarehouseId,
        ]);
    }

    public function store(SavePurchaseOrderRequest $request, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $userId = (string) Auth::id();

            // Calcular total de la solicitud
            $total = 0;
            $details = [];
            foreach ($validated['details'] as $detail) {
                $qty = (float)$detail['quantity'];
                $price = (float)$detail['unit_price'];
                $subtotal = $qty * $price;
                $total += $subtotal;

                $details[] = [
                    'product_id' => $detail['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $providerId = $validated['provider_id'] ?? null;
            if (!$providerId) {
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
                $providerId = $provider->id;
            }

            $orderData = [
                'provider_id' => $providerId,
                'warehouse_id' => $validated['warehouse_id'],
                'user_id' => $userId,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
                'total' => $total,
            ];

            $service->create($orderData, $details);

            // Si proviene de una solicitud de consumo, actualizar su estado a compras_generado
            if ($request->filled('from_consumption_id')) {
                \App\Models\ConsumptionRequest::where('id', $request->input('from_consumption_id'))
                    ->update(['status' => 'compras_generado']);
            }

            if ($request->filled('from_consumption_ids')) {
                $ids = $request->input('from_consumption_ids');
                if (is_array($ids)) {
                    \App\Models\ConsumptionRequest::whereIn('id', $ids)
                        ->update(['status' => 'compras_generado']);
                }
            }

            return redirect()->route('admin.purchase-orders.index')
                ->with('success', 'Solicitud de compra registrada con éxito.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al registrar la solicitud: ' . $e->getMessage()]);
        }
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['provider', 'warehouse', 'user', 'details.product.unitOfMeasure']);
        
        $user = auth()->user();
        $isAdmin = $user ? ($user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin) : false;

        return Inertia::render('Admin/PurchaseOrder/Show', [
            'purchaseOrder' => new PurchaseOrderResource($purchaseOrder),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function cancel(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        try {
            $user = auth()->user();
            if (!$user) {
                abort(403);
            }

            $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;

            // Solo el administrador o el creador de la orden pueden cancelarla si está pendiente
            if (!$isAdmin && $purchaseOrder->user_id !== $user->id) {
                return back()->withErrors(['error' => 'No tiene permisos para rechazar esta solicitud.']);
            }

            $service->changeStatus($purchaseOrder, 'cancelada');

            return back()->with('success', 'Solicitud rechazada/cancelada correctamente.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al cancelar la solicitud: ' . $e->getMessage()]);
        }
    }

    public function approve(PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;
        if (!$isAdmin) {
            return back()->withErrors(['error' => 'Solo los administradores pueden aprobar las solicitudes de compra.']);
        }

        try {
            $service->changeStatus($purchaseOrder, 'aprobada');

            return back()->with('success', 'Solicitud de compra aprobada administrativamente.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al aprobar la solicitud: ' . $e->getMessage()]);
        }
    }

    public function convertToPurchase(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $service): RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']) || $user->is_super_admin;
        $isWarehouse = $user->hasRole(['Almacén', 'almacen', 'Almacen', 'almacén']);
        $hasPermission = $user->can('manage-purchases');

        if (!$isAdmin && !$isWarehouse && !$hasPermission) {
            return back()->withErrors(['error' => 'No tiene permisos para registrar la compra física (requiere rol de Almacén o permisos de compras).']);
        }

        if ($purchaseOrder->status !== 'aprobada') {
            return back()->withErrors(['error' => 'Solo las solicitudes previamente aprobadas por el administrador pueden ser convertidas en compra física.']);
        }

        $request->validate([
            'voucher_type' => ['required', 'string', 'in:factura,recibo,boleta,otro'],
            'voucher_type_number' => ['required', 'string', 'max:100'],
            'payment_type' => ['required', 'string', 'in:efectivo,credito'],
            'due_date' => ['required_if:payment_type,credito', 'nullable', 'date'],
            'down_payment' => ['required_if:payment_type,credito', 'nullable', 'numeric', 'min:0'],
            'receipt_file' => ['nullable', 'file', 'mimes:jpeg,png,pdf', 'max:5120'], // Máximo 5MB
        ], [
            'voucher_type.required' => 'El tipo de comprobante es obligatorio.',
            'payment_type.required' => 'El método de pago es obligatorio.',
            'due_date.required_if' => 'La fecha de vencimiento es obligatoria para compras a crédito.',
            'voucher_type_number.required' => 'El número de comprobante es obligatorio.',
        ]);

        try {
            $receiptPath = null;
            if ($request->hasFile('receipt_file')) {
                $file = $request->file('receipt_file');
                $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $receiptPath = $file->storeAs('purchases/receipts', $filename, 'public');
                $receiptPath = '/storage/' . $receiptPath;
            }

            $dueDate = $request->payment_type === 'credito' ? $request->due_date : null;
            $downPayment = $request->payment_type === 'credito' ? (string)$request->down_payment : '0';

            // Prepend voucher number to notes before conversion
            if ($request->voucher_type_number) {
                $purchaseOrder->notes = "Nº de Comprobante: {$request->voucher_type_number}. " . ($purchaseOrder->notes ?? '');
                $purchaseOrder->save();
            }

            $service->convertToPurchase(
                $purchaseOrder->id,
                (string)$user->id,
                $request->voucher_type,
                $request->payment_type,
                $dueDate,
                $downPayment,
                $receiptPath
            );

            return redirect()->route('admin.purchases.index')
                ->with('success', 'Solicitud convertida a compra física con éxito. Stock e inventarios actualizados.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar la conversión a compra: ' . $e->getMessage()]);
        }
    }
}
