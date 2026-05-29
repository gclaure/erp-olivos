<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BiProductStats;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Facades\Branch;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dateRange = $request->input('dateRange', 'last_30_days');
        $warehouseId = $request->input('warehouseId');
        $productId = $request->input('productId');
        $branchId = Branch::getActiveBranchId();

        $dates = $this->getDates($dateRange);
        $startDate = $dates['start'];
        $endDate = $dates['end'];

        $product = null;
        if ($productId) {
            $product = Product::find($productId);
        }

        return Inertia::render('Admin/Reports/Index', [
            'filters' => [
                'dateRange' => $dateRange,
                'warehouseId' => $warehouseId,
                'productId' => $productId,
                'productName' => $product?->name ?? '',
            ],
            'warehouses' => Warehouse::where('is_active', true)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->get(['id', 'name']),
            'metrics' => $this->getSalesMetrics($startDate, $endDate, $branchId, $warehouseId, $productId),
            'salesTrend' => $this->getSalesTrendData($startDate, $endDate, $branchId, $warehouseId, $productId),
            'salesByCategory' => $this->getSalesByCategoryData($startDate, $endDate, $branchId, $warehouseId, $productId),
            'topProducts' => $this->getTopProductsData($startDate, $endDate, $branchId, $warehouseId, $productId),
            'stockValuationTrend' => $this->getStockValuationTrendData($startDate, $endDate, $branchId, $warehouseId),
            'inventoryFlow' => $this->getInventoryFlowData($startDate, $endDate, $branchId, $warehouseId, $productId),
            'abcAnalysis' => $this->getAbcAnalysis(),
        ]);
    }

    private function getDates(string $range): array
    {
        return match ($range) {
            'this_month' => [
                'start' => Carbon::now()->startOfMonth()->toDateString(),
                'end' => Carbon::now()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                'start' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end' => Carbon::now()->subMonth()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                'start' => Carbon::now()->startOfYear()->toDateString(),
                'end' => Carbon::now()->endOfYear()->toDateString(),
            ],
            default => [ // last_30_days
                'start' => Carbon::now()->subDays(30)->toDateString(),
                'end' => Carbon::now()->toDateString(),
            ],
        };
    }

    private function getSalesMetrics($startDate, $endDate, $branchId, $warehouseId, $productId): array
    {
        $query = DB::table('bi_sales_daily')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) $query->where('branch_id', $branchId);
        if ($warehouseId) $query->where('warehouse_id', $warehouseId);
        if ($productId) $query->where('product_id', $productId);

        $stats = $query->selectRaw('SUM(total_revenue) as revenue, SUM(total_profit) as profit, SUM(total_cost) as cost, SUM(total_quantity) as quantity')
            ->first();

        return [
            'revenue' => (float)($stats->revenue ?? 0),
            'profit' => (float)($stats->profit ?? 0),
            'cost' => (float)($stats->cost ?? 0),
            'quantity' => (float)($stats->quantity ?? 0),
        ];
    }

    private function getTopProductsData($startDate, $endDate, $branchId, $warehouseId, $productId): array
    {
        $query = DB::table('bi_sales_daily')
            ->join('products', 'bi_sales_daily.product_id', '=', 'products.id')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) $query->where('bi_sales_daily.branch_id', $branchId);
        if ($warehouseId) $query->where('bi_sales_daily.warehouse_id', $warehouseId);
        if ($productId) $query->where('bi_sales_daily.product_id', $productId);

        $top = $query->selectRaw('products.id, products.name, SUM(total_revenue) as revenue, SUM(total_quantity) as quantity')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return [
            'labels' => $top->pluck('name')->toArray(),
            'revenue' => $top->pluck('revenue')->map(fn($v) => (float)$v)->toArray(),
        ];
    }

    private function getSalesTrendData($startDate, $endDate, $branchId, $warehouseId, $productId): array
    {
        $query = DB::table('bi_sales_daily')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) $query->where('branch_id', $branchId);
        if ($warehouseId) $query->where('warehouse_id', $warehouseId);
        if ($productId) $query->where('product_id', $productId);

        $data = $query->selectRaw('date, SUM(total_revenue) as revenue, SUM(total_profit) as profit')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $revenue = [];
        $profit = [];

        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $labels[] = $date->format('d/m');
            
            if ($data->has($dateStr)) {
                $item = $data->get($dateStr);
                $revenue[] = (float)$item->revenue;
                $profit[] = (float)$item->profit;
            } else {
                $revenue[] = 0.0;
                $profit[] = 0.0;
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'profit' => $profit,
        ];
    }

    private function getInventoryFlowData($startDate, $endDate, $branchId, $warehouseId, $productId): array
    {
        $query = DB::table('bi_movements_daily')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) $query->where('branch_id', $branchId);
        if ($warehouseId) $query->where('warehouse_id', $warehouseId);
        if ($productId) $query->where('product_id', $productId);

        $data = $query->selectRaw('date, SUM(total_input_quantity) as inputs, SUM(total_output_quantity) as outputs')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $inputs = [];
        $outputs = [];

        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $labels[] = $date->format('d/m');
            
            if ($data->has($dateStr)) {
                $item = $data->get($dateStr);
                $inputs[] = (float)$item->inputs;
                $outputs[] = (float)$item->outputs;
            } else {
                $inputs[] = 0.0;
                $outputs[] = 0.0;
            }
        }

        return [
            'labels' => $labels,
            'inputs' => $inputs,
            'outputs' => $outputs,
        ];
    }

    private function getSalesByCategoryData($startDate, $endDate, $branchId, $warehouseId, $productId): array
    {
        $query = DB::table('bi_sales_daily')
            ->join('products', 'bi_sales_daily.product_id', '=', 'products.id')
            ->join('category_product', 'products.id', '=', 'category_product.product_id')
            ->join('categories', 'category_product.category_id', '=', 'categories.id')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) $query->where('bi_sales_daily.branch_id', $branchId);
        if ($warehouseId) $query->where('bi_sales_daily.warehouse_id', $warehouseId);
        if ($productId) $query->where('bi_sales_daily.product_id', $productId);

        $data = $query->selectRaw('categories.name as category, SUM(total_revenue) as revenue')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('revenue')
            ->get();

        return [
            'labels' => $data->pluck('category')->toArray(),
            'values' => $data->pluck('revenue')->map(fn($v) => (float)$v)->toArray(),
        ];
    }

    private function getStockValuationTrendData($startDate, $endDate, $branchId, $warehouseId): array
    {
        $query = DB::table('bi_inventory_daily')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($branchId) {
            $query->join('warehouses', 'bi_inventory_daily.warehouse_id', '=', 'warehouses.id')
                  ->where('warehouses.branch_id', $branchId);
        }

        if ($warehouseId) $query->where('bi_inventory_daily.warehouse_id', $warehouseId);

        $data = $query->selectRaw('date, SUM(total_value) as value')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $values = [];

        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $labels[] = $date->format('d/m');
            
            if ($data->has($dateStr)) {
                $item = $data->get($dateStr);
                $values[] = (float)$item->value;
            } else {
                $values[] = 0.0;
            }
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function getAbcAnalysis(): array
    {
        return [
            'A' => (int)BiProductStats::where('abc_rank', 'A')->count(),
            'B' => (int)BiProductStats::where('abc_rank', 'B')->count(),
            'C' => (int)BiProductStats::where('abc_rank', 'C')->count(),
        ];
    }
}
