<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ConsumerConsumptionsExport;
use App\Exports\InventoryStockExport;
use App\Exports\MonthlyMovementsExport;
use App\Facades\Branch;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\Kardex;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'consumptions');
        $branchId = Branch::getActiveBranchId();

        $warehouses = Warehouse::where('is_active', true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $consumers = User::where('is_active', true)
            ->where('is_super_admin', false)
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Super-admin', 'Super Administrador', 'Super-administrador', 'super administrador', 'super admin']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $reportData = match ($tab) {
            'movements' => $this->getMovementsData($request, $branchId),
            'stock' => $this->getStockData($request, $branchId),
            default => $this->getConsumptionsData($request, $branchId),
        };

        return Inertia::render('Admin/Reports/Index', [
            'activeTab' => $tab,
            'filters' => $request->all(),
            'warehouses' => $warehouses,
            'categories' => $categories,
            'consumers' => $consumers,
            'reportData' => $reportData,
        ]);
    }

    // ── 1. CONSUMOS POR CONSUMIDOR ───────────────────────────────────────────

    private function getConsumptionsQuery(Request $request, ?string $branchId)
    {
        $query = ConsumptionRequestDetail::query()
            ->with([
                'consumptionRequest.user:id,name,email',
                'consumptionRequest.warehouse:id,name',
                'consumptionRequest.dispatchedByUser:id,name',
                'product:id,name,code,unit_of_measure_id',
                'product.unitOfMeasure:id,name,abbreviation',
            ])
            ->whereHas('consumptionRequest', function ($q) use ($request, $branchId) {
                $q->whereHas('user', function ($u) {
                    $u->where('is_super_admin', false)
                      ->whereDoesntHave('roles', fn($r) => $r->whereIn('name', ['Super Admin', 'Super-admin', 'Super Administrador', 'Super-administrador', 'super administrador', 'super admin']));
                });
                if ($branchId) {
                    $q->whereHas('warehouse', fn($w) => $w->where('branch_id', $branchId));
                }
                if ($request->filled('user_id')) {
                    $q->where('user_id', $request->user_id);
                }
                if ($request->filled('warehouse_id')) {
                    $q->where('warehouse_id', $request->warehouse_id);
                }
                if ($request->filled('status')) {
                    $q->where('status', $request->status);
                }
                if ($request->filled('date_from')) {
                    $q->whereDate('date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $q->whereDate('date', '<=', $request->date_to);
                }
            });

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        return $query->orderByDesc(
            ConsumptionRequest::select('date')
                ->whereColumn('consumption_requests.id', 'consumption_request_details.consumption_request_id')
        )->orderByDesc('created_at');
    }

    private function getConsumptionsData(Request $request, ?string $branchId): array
    {
        $query = $this->getConsumptionsQuery($request, $branchId);
        $paginated = $query->paginate(20)->withQueryString();

        // Summary calculations
        $summaryQuery = (clone $query);
        $totalItems = $summaryQuery->count();
        $totalRequested = (float)(clone $summaryQuery)->sum('quantity_requested');
        $totalDelivered = (float)(clone $summaryQuery)->sum('quantity_delivered');

        $distinctRequestsCount = ConsumptionRequest::query()
            ->whereHas('user', function ($u) {
                $u->where('is_super_admin', false)
                  ->whereDoesntHave('roles', fn($r) => $r->whereIn('name', ['Super Admin', 'Super-admin', 'Super Administrador', 'Super-administrador', 'super administrador', 'super admin']));
            })
            ->when($branchId, fn($q) => $q->whereHas('warehouse', fn($w) => $w->where('branch_id', $branchId)))
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('warehouse_id'), fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('date', '<=', $request->date_to))
            ->count();

        return [
            'paginated' => $paginated,
            'summary' => [
                'total_requests' => $distinctRequestsCount,
                'total_items' => $totalItems,
                'total_requested' => $totalRequested,
                'total_delivered' => $totalDelivered,
            ],
        ];
    }

    public function downloadConsumptionsPdf(Request $request): StreamedResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $branchId = Branch::getActiveBranchId();
        $query = $this->getConsumptionsQuery($request, $branchId);
        $records = $query->limit(1000)->get();

        $user = $request->filled('user_id') ? User::find($request->user_id) : null;
        $warehouse = $request->filled('warehouse_id') ? Warehouse::find($request->warehouse_id) : null;

        $totalRequested = $records->sum('quantity_requested');
        $totalDelivered = $records->sum(fn($i) => $i->quantity_delivered ?? $i->quantity_requested);
        $totalRequests = $records->pluck('consumption_request_id')->unique()->count();

        $summary = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'consumer_name' => $user?->name,
            'warehouse_name' => $warehouse?->name,
            'total_requests' => $totalRequests,
            'total_items' => $records->count(),
            'total_requested' => $totalRequested,
            'total_delivered' => $totalDelivered,
        ];

        $company = Company::first();

        $pdf = Pdf::loadView('exports.reports.consumer-consumptions-pdf', [
            'records' => $records,
            'summary' => $summary,
            'company' => $company,
        ])->setPaper('a4', 'landscape');

        $filename = 'Reporte_Consumos_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadConsumptionsExcel(Request $request): BinaryFileResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $branchId = Branch::getActiveBranchId();
        $query = $this->getConsumptionsQuery($request, $branchId);

        $totalRequested = (clone $query)->sum('quantity_requested');
        $totalDelivered = (clone $query)->sum('quantity_delivered');

        $summaryData = [
            'total_requested' => (float)$totalRequested,
            'total_delivered' => (float)$totalDelivered,
        ];

        $filename = 'Reporte_Consumos_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ConsumerConsumptionsExport($query, $summaryData),
            $filename
        );
    }

    // ── 2. ENTRADAS Y SALIDAS MENSUALES ─────────────────────────────────────

    private function getMovementsQuery(Request $request, ?string $branchId)
    {
        $query = Kardex::query()
            ->with(['product:id,name,code,unit_of_measure_id', 'product.unitOfMeasure:id,name,abbreviation', 'warehouse:id,name', 'user:id,name'])
            ->when($branchId, fn($q) => $q->whereHas('warehouse', fn($w) => $w->where('branch_id', $branchId)));

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('movement_type')) {
            $type = $request->movement_type;
            if ($type === 'ENTRADA') {
                $query->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_POSITIVO', 'COMPRA']);
            } elseif ($type === 'SALIDA') {
                $query->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_NEGATIVO', 'CONSUMO', 'MERMA']);
            } else {
                $query->where('type', $type);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    private function getMovementsData(Request $request, ?string $branchId): array
    {
        $query = $this->getMovementsQuery($request, $branchId);
        $paginated = $query->paginate(20)->withQueryString();

        $entradasQuery = (clone $query)->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_POSITIVO', 'COMPRA']);
        $salidasQuery = (clone $query)->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_NEGATIVO', 'CONSUMO', 'MERMA']);

        return [
            'paginated' => $paginated,
            'summary' => [
                'total_entradas_qty' => (float)$entradasQuery->sum('quantity'),
                'total_entradas_val' => (float)$entradasQuery->sum('total_cost'),
                'total_salidas_qty' => (float)$salidasQuery->sum('quantity'),
                'total_salidas_val' => (float)$salidasQuery->sum('total_cost'),
            ],
        ];
    }

    public function downloadMovementsPdf(Request $request): StreamedResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $branchId = Branch::getActiveBranchId();
        $query = $this->getMovementsQuery($request, $branchId);
        $records = $query->limit(1000)->get();

        $warehouse = $request->filled('warehouse_id') ? Warehouse::find($request->warehouse_id) : null;

        $entradas = $records->filter(fn($k) => in_array(strtoupper($k->type ?? ''), ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_POSITIVO', 'COMPRA']));
        $salidas = $records->filter(fn($k) => in_array(strtoupper($k->type ?? ''), ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_NEGATIVO', 'CONSUMO', 'MERMA']));

        $summary = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'warehouse_name' => $warehouse?->name,
            'movement_type' => $request->movement_type,
            'total_entradas_qty' => $entradas->sum('quantity'),
            'total_entradas_val' => $entradas->sum('total_cost'),
            'total_salidas_qty' => $salidas->sum('quantity'),
            'total_salidas_val' => $salidas->sum('total_cost'),
        ];

        $company = Company::first();

        $pdf = Pdf::loadView('exports.reports.monthly-movements-pdf', [
            'records' => $records,
            'summary' => $summary,
            'company' => $company,
        ])->setPaper('a4', 'landscape');

        $filename = 'Reporte_Entradas_Salidas_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadMovementsExcel(Request $request): BinaryFileResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $branchId = Branch::getActiveBranchId();
        $query = $this->getMovementsQuery($request, $branchId);

        $entradasQuery = (clone $query)->whereIn('type', ['ENTRADA', 'DEVOLUCION_SALIDA', 'TRANSFERENCIA_ENTRADA', 'AJUSTE_POSITIVO', 'COMPRA']);
        $salidasQuery = (clone $query)->whereIn('type', ['SALIDA', 'DEVOLUCION_ENTRADA', 'TRANSFERENCIA_SALIDA', 'AJUSTE_NEGATIVO', 'CONSUMO', 'MERMA']);

        $summaryData = [
            'total_entradas_qty' => (float)$entradasQuery->sum('quantity'),
            'total_salidas_qty' => (float)$salidasQuery->sum('quantity'),
            'total_cost' => (float)(clone $query)->sum('total_cost'),
        ];

        $filename = 'Reporte_Entradas_Salidas_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new MonthlyMovementsExport($query, $summaryData),
            $filename
        );
    }

    // ── 3. STOCK DE INSUMOS ─────────────────────────────────────────────────

    private function getStockQuery(Request $request, ?string $branchId)
    {
        $query = Stock::query()
            ->with([
                'product:id,name,code,min_stock,unit_of_measure_id,type',
                'product.unitOfMeasure:id,name,abbreviation',
                'product.categories:id,name',
                'warehouse:id,name,branch_id',
            ])
            ->whereHas('warehouse', function ($w) use ($branchId, $request) {
                if ($branchId) {
                    $w->where('branch_id', $branchId);
                }
                if ($request->filled('warehouse_id')) {
                    $w->where('id', $request->warehouse_id);
                }
            });

        // Stock condition: default is stock > 0, unless "all" is explicitly passed
        $stockCondition = $request->input('stock_condition', 'with_stock');
        if ($stockCondition === 'with_stock') {
            $query->where('quantity', '>', 0);
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->category_id;
            $query->whereHas('product.categories', fn($c) => $c->where('categories.id', $categoryId));
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        return $query->orderByDesc('quantity');
    }

    private function getStockData(Request $request, ?string $branchId): array
    {
        $query = $this->getStockQuery($request, $branchId);
        $paginated = $query->paginate(20)->withQueryString();

        $totalItems = (clone $query)->count();
        $totalQuantity = (float)(clone $query)->sum('quantity');
        $totalValue = (float)(clone $query)->sum('inventory_value');

        // Low stock count: quantity <= min_stock (joining products table)
        $lowStockCount = (clone $query)
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->whereColumn('stocks.quantity', '<=', 'products.min_stock')
            ->count();

        return [
            'paginated' => $paginated,
            'summary' => [
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'low_stock_count' => $lowStockCount,
            ],
        ];
    }

    public function downloadStockPdf(Request $request): StreamedResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $branchId = Branch::getActiveBranchId();
        $query = $this->getStockQuery($request, $branchId);
        $records = $query->limit(1000)->get();

        $warehouse = $request->filled('warehouse_id') ? Warehouse::find($request->warehouse_id) : null;
        $category = $request->filled('category_id') ? Category::find($request->category_id) : null;

        $totalQty = $records->sum('quantity');
        $totalVal = $records->sum(fn($s) => $s->inventory_value ?? ($s->quantity * $s->average_cost));
        $lowStockCount = $records->filter(fn($s) => (float)$s->quantity <= (float)($s->product?->min_stock ?? 0))->count();

        $summary = [
            'only_with_stock' => $request->input('stock_condition', 'with_stock') === 'with_stock',
            'warehouse_name' => $warehouse?->name,
            'category_name' => $category?->name,
            'total_items' => $records->count(),
            'total_quantity' => $totalQty,
            'total_value' => $totalVal,
            'low_stock_count' => $lowStockCount,
        ];

        $company = Company::first();

        $pdf = Pdf::loadView('exports.reports.inventory-stock-pdf', [
            'records' => $records,
            'summary' => $summary,
            'company' => $company,
        ])->setPaper('a4', 'landscape');

        $filename = 'Reporte_Stock_Insumos_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function downloadStockExcel(Request $request): BinaryFileResponse
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $branchId = Branch::getActiveBranchId();
        $query = $this->getStockQuery($request, $branchId);

        $totalQuantity = (float)(clone $query)->sum('quantity');
        $totalValue = (float)(clone $query)->sum('inventory_value');

        $summaryData = [
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
        ];

        $filename = 'Reporte_Stock_Insumos_' . Carbon::now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new InventoryStockExport($query, $summaryData),
            $filename
        );
    }
}
