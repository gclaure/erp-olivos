<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kardex;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Http\Resources\KardexResource;

class KardexController extends Controller
{
    public function index(Request $request): Response
    {
        $dateFrom = $request->input('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->input('date_to', Carbon::now()->format('Y-m-d'));
        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');
        $type = $request->input('type');
        $perPage = (int) $request->input('per_page', 25);
        $sortOrder = $request->input('sort_order', 'desc');

        $user = auth()->user();
        $isAdmin = $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);

        $query = Kardex::query()->with(['product', 'warehouse', 'user', 'recordable']);

        // Aplicar restricción de sucursal si no es admin
        $query->when(!$isAdmin, function($q) use ($user) {
            return $q->whereHas('warehouse', fn($sq) => $sq->where('branch_id', $user->branch_id));
        });

        $query = $this->applyFilters($query, $productId, $warehouseId, $type, $dateFrom, $dateTo);

        $records = $query->orderBy('created_at', $sortOrder)
            ->orderBy('id', $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        $summaryStats = $this->calculateSummaryStats($productId, $warehouseId, $type, $dateFrom, $dateTo, $isAdmin, $user);

        $selectedProduct = null;
        if ($productId) {
            $product = Product::find($productId, ['id', 'name', 'code']);
            if ($product) {
                $selectedProduct = [
                    'id' => $product->id,
                    'name' => "{$product->name} - {$product->code}"
                ];
            }
        }

        $warehouses = Warehouse::query()
            ->when(!$isAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Kardex/Index', [
            'records' => KardexResource::collection($records),
            'summaryStats' => $summaryStats,
            'selectedProduct' => $selectedProduct,
            'warehouses' => $warehouses,
            'filters' => [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'type' => $type,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    private function applyFilters($query, $productId, $warehouseId, $type, $dateFrom, $dateTo)
    {
        return $query
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->when($type, function ($q) use ($type) {
                if ($type === 'ENTRADA') {
                    $q->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'PURCHASE']);
                } elseif ($type === 'SALIDA') {
                    $q->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);
                } else {
                    $q->where('type', $type);
                }
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo));
    }

    private function calculateSummaryStats($productId, $warehouseId, $type, $dateFrom, $dateTo, $isAdmin, $user): array
    {
        $baseQuery = Kardex::query();
        
        // Aplicar restricción de sucursal si no es admin
        $baseQuery->when(!$isAdmin, function($q) use ($user) {
            return $q->whereHas('warehouse', fn($sq) => $sq->where('branch_id', $user->branch_id));
        });

        $baseQuery = $this->applyFilters($baseQuery, $productId, $warehouseId, $type, $dateFrom, $dateTo);
        
        $entradasQuery = clone $baseQuery;
        $entradas = $entradasQuery->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'PURCHASE']);
        
        $salidasQuery = clone $baseQuery;
        $salidas = $salidasQuery->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA']);
        
        $lastRecordQuery = clone $baseQuery;
        $lastRecord = $lastRecordQuery->orderByDesc('created_at')->orderByDesc('id')->first();

        return [
            'total_entradas_qty' => (float) $entradas->sum('quantity'),
            'total_entradas_val' => (float) $entradas->sum('total_cost'),
            'total_salidas_qty'  => (float) $salidas->sum('quantity'),
            'total_salidas_val'  => (float) $salidas->sum('total_cost'),
            'saldo_qty'          => (float) ($lastRecord ? $lastRecord->balance_quantity : 0),
            'saldo_val'          => (float) ($lastRecord ? $lastRecord->balance_total_cost : 0),
        ];
    }
}
