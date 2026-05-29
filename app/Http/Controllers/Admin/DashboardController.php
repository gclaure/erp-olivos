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
        
        return Inertia::render('Admin/Dashboard', [
            'filters' => [
                'month' => $month,
                'year' => $year,
                'branch_id' => $isAdmin ? null : $user->branch_id,
            ],
            'stats' => $this->getStats($user, $month, $year, $isAdmin),
            'planUsage' => $this->getPlanUsage(),
            'salesChartData' => $this->getRequisitionsChartData($user, $year, $isAdmin),
            'topProducts' => $this->getTopRequestedProducts($user, $month, $year, $isAdmin),
            'categoryDistribution' => $this->getCategoryDistribution(),
            'inventoryMovements' => $this->getInventoryMovements($month, $year),
            'stockByWarehouse' => $this->getStockByWarehouse(),
            'lowStockProducts' => $this->getLowStockProducts(),
        ]);
    }

    private function getStats(User $user, int $month, int $year, bool $isAdmin): array
    {
        $requestsQuery = ConsumptionRequest::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        $monthRequestsCount = (clone $requestsQuery)->count();

        $pendingRequestsCount = ConsumptionRequest::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->whereIn('status', ['pendiente', 'parcial'])
            ->count();

        $inventoryValue = Stock::join('products', 'stocks.product_id', '=', 'products.id')
            ->selectRaw('SUM(stocks.quantity * products.price) as total_value')
            ->value('total_value') ?? 0;

        $lowStockCount = Product::withSum('stocks as total_stock', 'quantity')
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
            // Retrocompatibilidad con nombres de variables anteriores en frontend
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

    private function getRequisitionsChartData(User $user, int $year, bool $isAdmin): array
    {
        $requestsData = ConsumptionRequest::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
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
                'label' => "Requisiciones de {$year}",
                'data' => $data,
                'borderColor' => '#4f46e5',
                'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                'fill' => true,
                'tension' => 0.4
            ]]
        ];
    }

    private function getTopRequestedProducts(User $user, int $month, int $year, bool $isAdmin): array
    {
        return ConsumptionRequestDetail::whereHas('consumptionRequest', function ($q) use ($month, $year, $isAdmin) {
                $q->when($isAdmin, fn($sq) => $sq->withoutGlobalScope('branch_scoped'))
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

    private function getCategoryDistribution(): array
    {
        return Category::with(['products.stocks' => function($q) {
                $q->select('product_id', 'quantity');
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

    private function getInventoryMovements(int $month, int $year): array
    {
        $isAdmin = Auth::user()->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);
        
        $entries = Kardex::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->whereIn('type', ['entrada', 'ENTRADA', 'compra', 'COMPRA', 'transferencia_entrada'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('quantity');
            
        $exits = Kardex::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->whereIn('type', ['salida', 'SALIDA', 'venta', 'VENTA', 'transferencia_salida', 'anulacion', 'perdida'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('quantity');

        return [
            'entries' => (float)$entries,
            'exits' => (float)$exits
        ];
    }

    private function getStockByWarehouse(): array
    {
        $isAdmin = Auth::user()->hasRole(['Admin', 'admin', 'Administrador', 'administrador']);
        
        return Warehouse::query()
            ->when($isAdmin, fn($q) => $q->withoutGlobalScope('branch_scoped'))
            ->withSum('stocks as total_stock', 'quantity')
            ->get()
            ->map(fn($w) => [
                'name' => $w->name,
                'total' => (float)$w->total_stock
            ])
            ->toArray();
    }

    private function getLowStockProducts(): array
    {
        return Product::withSum('stocks as total_stock', 'quantity')
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
