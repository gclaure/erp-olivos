<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SaleResource;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $warehouseFilter = $request->input('warehouseFilter');
        $search = $request->input('search');

        $user = auth()->user();
        $isAdmin = $user->is_super_admin || $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);

        $sales = Sale::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->with(['client', 'warehouse', 'user'])
            // Si no es admin, filtrar por su sucursal y solo sus ventas
            ->when(!$isAdmin, function($q) use ($user) {
                $q->whereHas('warehouse', fn($sq) => $sq->where('branch_id', $user->branch_id))
                  ->where('user_id', $user->id);
            })
            ->when($warehouseFilter, fn ($q) => $q->where('warehouse_id', $warehouseFilter))
            ->when($search, fn ($q) => $q->where(fn($sq) => 
                $sq->where('number', 'ilike', "%{$search}%")
                   ->orWhereHas('client', fn($cq) => $cq->where('name', 'ilike', "%{$search}%"))
            ))
            ->orderByDesc('date')
            ->orderByDesc('number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Sales/Index', [
            'sales' => SaleResource::collection($sales),
            'filters' => [
                'search' => $search,
                'warehouseFilter' => $warehouseFilter,
                'posFilter' => null,
            ],
            'warehouses' => Warehouse::query()
                ->when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'pointOfSales' => [],
            'users' => User::query()
                ->when(!$isAdmin, fn($q) => $q->where('id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'initialDates' => [
                'from' => now()->startOfMonth()->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
            ],
            'isAdmin' => $isAdmin,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load(['client', 'user', 'warehouse', 'details.product']));
    }

    /**
     * Annul the specified resource.
     */
    public function annul(Request $request, Sale $sale, SaleService $service): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|min:5|max:255',
        ]);

        if ($sale->status === 'cancelada') {
            return back()->with('error', 'La venta ya se encuentra anulada.');
        }

        $service->annul($sale, $request->input('reason'));

        return back()->with('success', 'La venta ha sido anulada correctamente, el stock restaurado y el historial actualizado.');
    }
}
