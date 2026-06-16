<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Kardex;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\User;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->integer('month', (int)now()->month);
        $year = $request->integer('year', (int)now()->year);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $isAdmin = $user->is_super_admin || $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);
        $isConsumer = $user->hasRole(['Consumidor', 'consumidor']) && !$isAdmin;
        $isWarehouse = $user->hasRole(['Almacén', 'almacen']) && !$isAdmin && !$isConsumer;

        if ($isConsumer) {
            return Inertia::render('Admin/ConsumerDashboard', [
                'filters' => [
                    'month' => $month,
                    'year' => $year,
                ],
                'stats' => $this->getConsumerStats($user, $month, $year),
                'salesChartData' => $this->getConsumerRequisitionsChartData($user, $year),
                'topProducts' => $this->getConsumerTopProducts($user, $month, $year),
                'categoryDistribution' => $this->getConsumerCategoryDistribution($user, $month, $year),
                'recentRequests' => $this->getConsumerRecentRequests($user),
            ]);
        }

        // Obtener los almacenes asignados para el rol de Almacén
        $warehouseIds = null;
        $assignedWarehouses = [];
        if ($isWarehouse) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id')->toArray();
            if (empty($warehouseIds)) {
                $warehouseIds = Warehouse::where('branch_id', $user->branch_id)->pluck('id')->toArray();
            }
            $assignedWarehouses = Warehouse::whereIn('id', $warehouseIds)->pluck('name')->toArray();
        }
        
        return Inertia::render('Admin/Dashboard', [
            'filters' => [
                'month' => $month,
                'year' => $year,
                'branch_id' => $isAdmin ? null : $user->branch_id,
            ],
            'isWarehouse' => $isWarehouse,
            'assignedWarehouses' => $assignedWarehouses,
            'stats' => $this->getStats($user, $month, $year, $isAdmin, $warehouseIds),
            'planUsage' => $this->getPlanUsage(),
            'salesChartData' => $this->getRequisitionsChartData($user, $year, $isAdmin, $warehouseIds),
            'topProducts' => $this->getTopRequestedProducts($user, $month, $year, $isAdmin, $warehouseIds),
            'categoryDistribution' => $this->getCategoryDistribution($warehouseIds),
            'inventoryMovements' => $this->getInventoryMovements($month, $year, $warehouseIds),
            'stockByWarehouse' => $this->getStockByWarehouse($warehouseIds),
            'lowStockProducts' => $this->getLowStockProducts($warehouseIds),
        ]);
    }

    private function getConsumerStats(User $user, int $month, int $year): array
    {
        $monthRequestsCount = ConsumptionRequest::query()
            ->where('user_id', $user->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->count();

        $pendingRequestsCount = ConsumptionRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pendiente', 'aprobado', 'despachado', 'despachado_parcial'])
            ->count();

        $observedRequestsCount = ConsumptionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'observado')
            ->count();

        $receivedRequestsCount = ConsumptionRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'recibido')
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->count();

        return [
            'month_requests' => $monthRequestsCount,
            'pending_requests' => $pendingRequestsCount,
            'observed_requests' => $observedRequestsCount,
            'received_requests' => $receivedRequestsCount,
        ];
    }

    private function getConsumerRequisitionsChartData(User $user, int $year): array
    {
        $requestsData = ConsumptionRequest::query()
            ->where('user_id', $user->id)
            ->whereYear('date', $year)
            ->selectRaw('CAST(EXTRACT(MONTH FROM date) AS INTEGER) as month, COUNT(id) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->all();

        $data = [];
        $labels = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create($year, $i, 1)->translatedFormat('M');
            $data[] = (float)($requestsData[$i] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => "Mis Solicitudes de {$year}",
                'data' => $data,
                'borderColor' => '#4f46e5',
                'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                'fill' => true,
                'tension' => 0.4
            ]]
        ];
    }

    private function getConsumerTopProducts(User $user, int $month, int $year): array
    {
        return ConsumptionRequestDetail::whereHas('consumptionRequest', function ($q) use ($user, $month, $year) {
                $q->where('user_id', $user->id)
                  ->whereYear('date', $year)
                  ->whereMonth('date', $month);
            })
            ->selectRaw('product_id, SUM(quantity_requested) as total_qty')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->map(fn($detail) => [
                'name' => $detail->product?->name ?? 'Desconocido',
                'total' => (float)$detail->total_qty
            ])
            ->toArray();
    }

    private function getConsumerCategoryDistribution(User $user, int $month, int $year): array
    {
        $userRequestDetails = ConsumptionRequestDetail::whereHas('consumptionRequest', function($q) use ($user, $month, $year) {
                $q->where('user_id', $user->id)
                  ->whereYear('date', $year)
                  ->whereMonth('date', $month);
            })
            ->with('product.categories')
            ->get();

        $categoriesCount = [];
        foreach ($userRequestDetails as $detail) {
            if ($detail->product) {
                foreach ($detail->product->categories as $category) {
                    if (!isset($categoriesCount[$category->name])) {
                        $categoriesCount[$category->name] = 0;
                    }
                    $categoriesCount[$category->name] += (float)$detail->quantity_requested;
                }
            }
        }

        $categoryDistribution = [];
        foreach ($categoriesCount as $name => $total) {
            $categoryDistribution[] = [
                'name' => $name,
                'total' => $total,
            ];
        }

        return $categoryDistribution;
    }

    private function getConsumerRecentRequests(User $user): array
    {
        return ConsumptionRequest::where('user_id', $user->id)
            ->with('warehouse')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'number' => $r->number,
                'formatted_number' => $r->formatted_number,
                'date' => $r->date->format('Y-m-d'),
                'warehouse_name' => $r->warehouse->name ?? 'N/A',
                'status' => $r->status,
            ])
            ->toArray();
    }

    private function getStats(User $user, int $month, int $year, bool $isAdmin, ?array $warehouseIds = null): array
    {
        $requestsQuery = ConsumptionRequest::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        $monthRequestsCount = (clone $requestsQuery)->count();

        $pendingRequestsCount = ConsumptionRequest::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereIn('status', ['pendiente', 'parcial'])
            ->count();

        $inventoryValue = Stock::join('products', 'stocks.product_id', '=', 'products.id')
            ->when($warehouseIds, fn($q) => $q->whereIn('stocks.warehouse_id', $warehouseIds))
            ->selectRaw('SUM(stocks.quantity * products.price) as total_value')
            ->value('total_value') ?? 0;

        $lowStockCount = Product::withSum(['stocks as total_stock' => function ($q) use ($warehouseIds) {
                $q->when($warehouseIds, fn($sq) => $sq->whereIn('warehouse_id', $warehouseIds));
            }], 'quantity')
            ->select(['id', 'min_stock'])
            ->get()
            ->filter(fn($p) => $p->total_stock <= $p->min_stock)
            ->count();

        $purchaseOrdersCount = PurchaseOrder::query()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->count();

        $todayRequestsCount = ConsumptionRequest::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereDate('date', now()->format('Y-m-d'))
            ->count();

        return [
            'month_requests' => $monthRequestsCount,
            'pending_requests' => $pendingRequestsCount,
            'inventory_value' => (float)$inventoryValue,
            'low_stock' => $lowStockCount,
            'total_products' => Product::count(),
            'purchase_orders_count' => $purchaseOrdersCount,
            'today_requests_count' => $todayRequestsCount,
            'revenue' => (float)$monthRequestsCount, 
            'is_seller' => false,
            'seller_sales_count' => $purchaseOrdersCount,
            'today_revenue' => (float)$todayRequestsCount,
            'today_sales_count' => $pendingRequestsCount,
            'plan_name' => 'Logística e Inventario',
        ];
    }

    private function getPlanUsage(): array
    {
        return Cache::remember("plan_usage_single", now()->addMinutes(30), function () {
            return [
                'users' => [
                    'current' => User::where('is_super_admin', false)->count(),
                    'limit' => -1,
                ],
                'branches' => [
                    'current' => Branch::withoutGlobalScope('branch_scoped')->count(),
                    'limit' => -1,
                ],
                'warehouses' => [
                    'current' => Warehouse::count(),
                    'limit' => -1,
                ],
                'pos' => [
                    'current' => 0,
                    'limit' => -1,
                ],
            ];
        });
    }

    private function getRequisitionsChartData(User $user, int $year, bool $isAdmin, ?array $warehouseIds = null): array
    {
        $requestsData = ConsumptionRequest::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereYear('date', $year)
            ->selectRaw('CAST(EXTRACT(MONTH FROM date) AS INTEGER) as month, COUNT(id) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month')
            ->all();

        $data = [];
        $labels = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = Carbon::create($year, $i, 1)->translatedFormat('M');
            $data[] = (float)($requestsData[$i] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => "Solicitudes de {$year}",
                'data' => $data,
                'borderColor' => '#4f46e5',
                'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                'fill' => true,
                'tension' => 0.4
            ]]
        ];
    }

    private function getTopRequestedProducts(User $user, int $month, int $year, bool $isAdmin, ?array $warehouseIds = null): array
    {
        return ConsumptionRequestDetail::whereHas('consumptionRequest', function ($q) use ($month, $year, $isAdmin, $warehouseIds) {
                $q->when($isAdmin, fn($sq) => $sq->withoutGlobalScope('branch_scoped'))
                  ->when($warehouseIds, fn($sq) => $sq->whereIn('warehouse_id', $warehouseIds))
                  ->whereYear('date', $year)
                  ->whereMonth('date', $month);
            })
            ->selectRaw('product_id, SUM(quantity_requested) as total_qty')
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get()
            ->map(fn($detail) => [
                'name' => $detail->product?->name ?? 'Desconocido',
                'total' => (float)$detail->total_qty
            ])
            ->toArray();
    }

    private function getCategoryDistribution(?array $warehouseIds = null): array
    {
        return Category::with(['products.stocks' => function($q) use ($warehouseIds) {
                $q->select('product_id', 'quantity')->when($warehouseIds, fn($sq) => $sq->whereIn('warehouse_id', $warehouseIds));
            }])
            ->get()
            ->map(function($category) {
                $totalStock = $category->products->sum(fn($p) => $p->stocks->sum('quantity'));
                return [
                    'name' => $category->name,
                    'total' => (float)$totalStock
                ];
            })
            ->filter(fn($c) => $c['total'] > 0)
            ->values()
            ->toArray();
    }

    private function getInventoryMovements(int $month, int $year, ?array $warehouseIds = null): array
    {
        $isAdmin = Auth::user()->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);
        
        $entries = Kardex::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereIn('type', [
                'entrada', 'ENTRADA', 'compra', 'COMPRA', 'transferencia_entrada',
                'PURCHASE', 'TRANSFER_IN', 'ADJUSTMENT_IN', 'INITIAL_LAYER'
            ])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('quantity');
            
        $exits = Kardex::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereIn('type', [
                'salida', 'SALIDA', 'venta', 'VENTA', 'transferencia_salida', 'anulacion', 'perdida',
                'SALE', 'TRANSFER_OUT', 'ADJUSTMENT_OUT', 'RETURN'
            ])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('quantity');

        return [
            'entries' => (float)$entries,
            'exits' => (float)$exits
        ];
    }

    private function getStockByWarehouse(?array $warehouseIds = null): array
    {
        $isAdmin = Auth::user()->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);
        
        return Warehouse::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->when($warehouseIds, fn($q) => $q->whereIn('id', $warehouseIds))
            ->withSum('stocks as total_stock', 'quantity')
            ->get()
            ->map(fn($w) => [
                'name' => $w->name,
                'total' => (float)$w->total_stock
            ])
            ->toArray();
    }

    private function getLowStockProducts(?array $warehouseIds = null): array
    {
        return Product::withSum(['stocks as total_stock' => function ($q) use ($warehouseIds) {
                $q->when($warehouseIds, fn($sq) => $sq->whereIn('warehouse_id', $warehouseIds));
            }], 'quantity')
            ->select(['id', 'name', 'code', 'min_stock'])
            ->get()
            ->filter(fn($p) => $p->total_stock <= $p->min_stock)
            ->take(5)
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'min_stock' => (float)$p->min_stock,
                'total_stock' => (float)$p->total_stock,
            ])
            ->toArray();
    }
}
