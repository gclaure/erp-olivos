<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\POS\ProcessQuotationAction;
use App\Actions\Admin\POS\ProcessSaleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\POS\StoreSaleRequest;
use App\Http\Resources\Admin\POS\POSProductResource;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\Warehouse;
use App\Facades\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    /**
     * Display the POS interface.
     */
    public function index(Request $request, ?\App\Models\Quotation $quotation = null): Response
    {
        $branchId = Branch::getActiveBranchId();
        
        // Configuración inicial
        $initialConfig = [
            'activeWarehouseId' => Branch::getActiveWarehouseId(),
            'activePosId' => null,
            'warehouseName' => Branch::getActiveWarehouse()?->name ?? 'ALMACÉN CENTRAL',
            'posName' => 'Consumo',
            'isFixedDiscount' => false,
            'receiptType' => \App\Facades\CompanyFacade::getCompany()?->receipt_type ?? 'media',
            'operationType' => $request->get('type', 'sale'),
            'defaultClient' => null,
            'permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray(),
        ];

        $pointsOfSale = [];
        $warehouses = [];

        if ($branchId) {
            $branchObj = \App\Models\Branch::find($branchId);
            $initialConfig['isFixedDiscount'] = $branchObj->is_fixed_discount ?? false;
            
            // Obtener Almacenes de la sucursal
            $warehouses = Warehouse::where('branch_id', $branchId)
                ->select('id', 'name')
                ->where('is_active', true)
                ->get();
        }

        // Cargar historial de logística para autocompletado (Lazy Loading)
        $shippingHistory = fn() => [
            'companies' => \App\Models\Sale::whereNotNull('shipping_company')->where('shipping_company', '!=', '')->distinct()->pluck('shipping_company'),
            'origins' => \App\Models\Sale::whereNotNull('shipping_origin')->where('shipping_origin', '!=', '')->distinct()->pluck('shipping_origin'),
            'destinations' => \App\Models\Sale::whereNotNull('shipping_destination')->where('shipping_destination', '!=', '')->distinct()->pluck('shipping_destination'),
        ];

        // Si hay una cotización, cargar sus detalles
        $initialQuotation = null;
        if ($quotation && $quotation->status === 'pendiente') {
            $quotation->load(['details.product.stocks' => function($q) use ($initialConfig) {
                $q->where('warehouse_id', $initialConfig['activeWarehouseId']);
            }, 'client']);
            
                    $initialQuotation = [
                'id' => $quotation->id,
                'client_id' => $quotation->client_id,
                'client_name' => $quotation->client->name,
                'client_phone' => $quotation->client->phone ?? '',
                'global_discount' => $quotation->global_discount,
                'items' => $quotation->details->map(function($detail) use ($initialConfig) {
                    $product = $detail->product;
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'price' => (float)$detail->unit_price,
                        'quantity' => (float)$detail->quantity,
                        'discount' => (float)$detail->discount,
                        'unit_cost' => (float)($product->kardexes()->where('warehouse_id', $initialConfig['activeWarehouseId'])->latest()->first()?->avg_cost ?? 0),
                        'max_stock' => (float)($product->stocks->first()?->quantity ?? 0),
                    ];
                }),
            ];
        }

        return Inertia::render('Admin/POS/Index', [
            'initialConfig' => $initialConfig,
            'pointsOfSale' => $pointsOfSale,
            'warehouses' => $warehouses,
            'shippingHistory' => $shippingHistory,
            'initialQuotation' => $initialQuotation,
        ]);
    }

    /**
     * Update the active POS or Warehouse.
     */
    public function updateContext(Request $request): RedirectResponse
    {
        if ($request->has('warehouse_id')) {
            Branch::setActiveWarehouse($request->warehouse_id);
        }

        return redirect()->back()->with('flash', [
            'success' => 'Contexto actualizado correctamente.'
        ]);
    }

    /**
     * Update the receipt type for the active POS.
     */
    public function updateReceiptType(Request $request): RedirectResponse
    {
        $request->validate([
            'receipt_type' => ['required', 'string', 'in:rollo,media'],
        ]);

        $company = \App\Facades\CompanyFacade::getCompany();
        if ($company) {
            $company->update([
                'receipt_type' => $request->receipt_type
            ]);
        }

        return redirect()->back();
    }


    /**
     * Store a new sale or quotation.
     */
    public function store(
        StoreSaleRequest $request,
        ProcessSaleAction $processSaleAction,
        ProcessQuotationAction $processQuotationAction
    ): RedirectResponse {
        $data = $request->validated();

        if ($data['operation_type'] === 'sale') {
            $sale = $processSaleAction->execute($data);
            return redirect()->back()->with([
                'success' => 'Venta procesada exitosamente.',
                'success_data' => [
                    'id' => $sale->id,
                    'type' => 'sale',
                ]
            ]);
        }

        $quotation = $processQuotationAction->execute($data);
        return redirect()->back()->with([
            'success' => 'Cotización guardada exitosamente.',
            'success_data' => [
                'id' => $quotation->id,
                'type' => 'quotation',
            ]
        ]);
    }

    /**
     * API for products search.
     */
    public function searchProducts(Request $request)
    {
        $warehouseId = $request->get('warehouse_id') ?? \App\Facades\Branch::getActiveWarehouseId();
        $search = $request->get('search');

        if (!$warehouseId) {
            return POSProductResource::collection(collect([]));
        }

        $kardexSubquery = \App\Models\Kardex::select('avg_cost')
            ->whereColumn('product_id', 'products.id')
            ->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->limit(1);

        $salesReservedSubquery = \App\Models\SaleDetail::query()
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->whereColumn('sale_details.product_id', 'products.id')
            ->where('sales.warehouse_id', $warehouseId)
            ->where('sales.is_delivered', false)
            ->where('sales.is_active', true)
            ->selectRaw('COALESCE(SUM(sale_details.quantity), 0)');

        $consumptionReservedSubquery = \App\Models\ConsumptionRequestDetail::query()
            ->join('consumption_requests', 'consumption_requests.id', '=', 'consumption_request_details.consumption_request_id')
            ->whereColumn('consumption_request_details.product_id', 'products.id')
            ->where('consumption_requests.warehouse_id', $warehouseId)
            ->whereIn('consumption_requests.status', ['pendiente', 'parcial', 'despachado_parcial', 'compras_generado'])
            ->selectRaw('COALESCE(SUM(consumption_request_details.quantity_requested - consumption_request_details.quantity_delivered), 0)');

        $expirationSubquery = \App\Models\Kardex::select('expiration_date')
            ->whereColumn('product_id', 'products.id')
            ->where('warehouse_id', $warehouseId)
            ->where('is_fifo_layer', true)
            ->where('remaining_quantity', '>', 0)
            ->whereNotNull('expiration_date')
            ->orderBy('expiration_date', 'asc')
            ->limit(1);

        $products = Product::query()
            ->where('is_active', true)
            ->select([
                'id', 
                'name', 
                'code', 
                'price', 
                'image_path', 
                'unit_of_measure_id',
                'units_per_package',
                'package_name',
                'location',
                'brand',
                'has_expiration'
            ])
            ->addSelect(['unit_cost' => $kardexSubquery])
            ->addSelect(['sales_reserved' => $salesReservedSubquery])
            ->addSelect(['consumption_reserved' => $consumptionReservedSubquery])
            ->addSelect(['nearest_expiration_date' => $expirationSubquery])
            ->with([
                'stocks' => function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }, 
                'unitOfMeasure:id,name,abbreviation',
                'categories:id,name'
            ])
            ->when($search, function($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);

        return POSProductResource::collection($products);
    }

    /**
     * API for clients search.
     */
    public function searchClients(Request $request)
    {
        return response()->json([]);
    }
}
