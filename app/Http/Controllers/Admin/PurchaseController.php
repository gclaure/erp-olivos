<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\PurchaseTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavePurchaseRequest;
use App\Http\Resources\PurchaseDetailResource;
use App\Http\Resources\PurchaseResource;
use App\Imports\PurchaseImport;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CancelPurchaseService;
use App\Services\PurchaseService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PurchaseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $paymentType = $request->get('payment_type');
        $onlyWithDebt = $request->boolean('only_with_debt');

        $user = auth()->user();
        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);

        $query = Purchase::query()
            ->with(['provider', 'warehouse', 'user', 'accountPayable'])
            // Si no es admin, filtrar por sucursal y usuario
            ->when(!$isAdmin, function($q) use ($user) {
                $q->whereHas('warehouse', fn($sq) => $sq->where('branch_id', $user->branch_id))
                  ->where('user_id', $user->id);
            })
            ->when($search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->whereHas('provider', fn ($sq) => $sq->where('name', 'ilike', "%{$search}%"))
                      ->orWhere('purchase_number', 'ilike', "%{$search}%");
                });
            })
            ->when($paymentType, fn ($q) => $q->where('payment_type', $paymentType))
            ->when($onlyWithDebt, fn ($q) => $q->whereHas('accountPayable', fn ($aq) => $aq->where('status', '!=', 'PAID')))
            ->orderByDesc('date');

        $purchases = $query->paginate(15)->withQueryString();
        
        $warehouses = Warehouse::query()
            ->when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Purchase/Index', [
            'records' => PurchaseResource::collection($purchases),
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $search,
                'payment_type' => $paymentType,
                'only_with_debt' => $onlyWithDebt,
            ],
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();
        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);

        return Inertia::render('Admin/Purchase/Create', [
            'warehouses' => Warehouse::query()
                ->when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function store(SavePurchaseRequest $request, PurchaseService $service): RedirectResponse
    {
        try {
            $service->createPurchase(
                array_merge($request->validated(), [
                    'user_id' => Auth::id() ?? User::first()->id,
                    'total' => $this->calculateTotal($request->details),
                    'status' => $request->payment_type === 'credito' ? 'pendiente' : 'completada'
                ]),
                $request->details
            );

            return redirect()->route('admin.purchases.index')->with('success', 'Compra registrada con éxito.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al registrar la compra: ' . $e->getMessage()]);
        }
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load(['provider', 'warehouse', 'user', 'details.product.unitOfMeasure', 'accountPayable']);
        
        return Inertia::render('Admin/Purchase/Show', [
            'purchase' => new PurchaseResource($purchase),
        ]);
    }

    public function details(Purchase $purchase): \Illuminate\Http\JsonResponse
    {
        $details = $purchase->details()
            ->with(['product'])
            ->paginate(10);

        return response()->json([
            'data' => PurchaseDetailResource::collection($details),
            'meta' => [
                'current_page' => $details->currentPage(),
                'last_page' => $details->lastPage(),
                'total' => $details->total(),
                'per_page' => $details->perPage(),
            ]
        ]);
    }

    public function cancel(Request $request, Purchase $purchase, CancelPurchaseService $service): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|min:5|max:255'
        ]);

        try {
            $service->cancel($purchase, $request->reason, Auth::id());
            return back()->with('success', 'Compra cancelada exitosamente.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Error al cancelar: ' . $e->getMessage()]);
        }
    }

    public function downloadTemplate(): BinaryFileResponse
    {
        return Excel::download(new PurchaseTemplateExport, 'plantilla_compras.xlsx');
    }

    public function import(Request $request, PurchaseService $service): RedirectResponse
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        try {
            Excel::import(
                new PurchaseImport($service, $request->warehouse_id, (string) Auth::id()),
                $request->file('import_file')
            );

            return back()->with('success', 'Compras importadas correctamente.');
        } catch (Exception $e) {
            return back()->withErrors(['import_file' => 'Error al importar: ' . $e->getMessage()]);
        }
    }

    private function calculateTotal(array $details): float
    {
        $total = 0;
        foreach ($details as $detail) {
            $total += (float)$detail['quantity'] * (float)$detail['unit_price'];
        }
        return (float)number_format($total, 2, '.', '');
    }
}
