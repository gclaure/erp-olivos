<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\UnitOfMeasure;
use App\Models\Warehouse;
use App\Models\Provider;
use App\Services\ProductService;
use App\Services\UnifiedImportService;
use App\Exports\ProductTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Models\ImportLog;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(Request $request): Response
    {
        $warehouseFilterId = $request->input('warehouse_id');
        $onlyLowStock = $request->boolean('low_stock');
        $search = $request->input('search');

        $products = Product::query()
            ->select(['products.id', 'products.name', 'products.code', 'products.price', 'products.min_stock', 'products.is_active', 'products.has_expiration', 'products.unit_of_measure_id', 'products.units_per_package', 'products.package_name', 'products.image_path', 'products.drive_links', 'products.location', 'products.brand', 'products.slug', 'products.description'])
            ->with(['categories:id,name', 'unitOfMeasure:id,abbreviation', 'stocks.warehouse:id,name'])
            ->withSum(['stocks as current_stock' => function ($query) use ($warehouseFilterId) {
                if ($warehouseFilterId) {
                    $query->where('warehouse_id', $warehouseFilterId);
                }
            }], 'quantity')
            ->when($search, fn ($q) => $q->where(fn($subQ) => 
                $subQ->where('products.name', 'ilike', "%{$search}%")
                     ->orWhere('products.code', 'ilike', "%{$search}%")
            ))
            ->when($onlyLowStock, function ($query) use ($warehouseFilterId) {
                $warehouseCondition = $warehouseFilterId 
                    ? "AND warehouse_id = '{$warehouseFilterId}'" 
                    : "";
                
                $query->whereRaw("(SELECT COALESCE(SUM(quantity), 0) FROM stocks WHERE stocks.product_id = products.id {$warehouseCondition}) <= products.min_stock");
            })
            ->orderBy('products.name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Admin/Product/Index', [
            'products' => ProductResource::collection($products),
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseFilterId,
                'low_stock' => $onlyLowStock,
            ],
            'categories' => fn() => Category::select('id', 'name')->orderBy('name')->get(),
            'units' => fn() => UnitOfMeasure::select('id', 'name', 'abbreviation')->orderBy('name')->get(),
            'warehouses' => fn() => Warehouse::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'providers' => fn() => Provider::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        if (empty($data['code'])) {
            $data['code'] = $this->productService->generateSku($data['name'], $request->category_ids);
        }
        
        $driveLinks = $request->input('drive_links', []);
        $processedLinks = [];

        // Procesar URLs (si son externas, las descargamos y convertimos)
        foreach ($driveLinks as $link) {
            if (str_starts_with($link, 'http') && !str_contains($link, config('app.url'))) {
                try {
                    $processedLinks[] = $this->productService->downloadAndConvertImage($link);
                } catch (\Exception $e) {
                    $processedLinks[] = $link; // Fallback al original si falla
                }
            } else {
                $processedLinks[] = $link;
            }
        }
        
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $processedLinks[] = $this->productService->processImage($image);
            }
        }
        
        $data['drive_links'] = $processedLinks;
        $data['image_path'] = !empty($processedLinks) ? $processedLinks[0] : null;



        $product = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            $product->categories()->sync($request->category_ids);

            // Registrar el stock inicial en cada almacén seleccionado con cantidad 0.00
            foreach ($request->warehouse_ids as $warehouseId) {
                \App\Models\Stock::firstOrCreate([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                ], [
                    'quantity' => 0.00,
                    'inventory_value' => 0.00,
                    'average_cost' => 0.00,
                ]);
            }

            return $product;
        });

        return back()->with('success', 'Producto creado correctamente.');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        $driveLinks = $request->input('drive_links', []);
        $processedLinks = [];

        // Procesar URLs (si son externas, las descargamos y convertimos)
        foreach ($driveLinks as $link) {
            if (str_starts_with($link, 'http') && !str_contains($link, config('app.url'))) {
                try {
                    $processedLinks[] = $this->productService->downloadAndConvertImage($link);
                } catch (\Exception $e) {
                    $processedLinks[] = $link; // Fallback al original si falla
                }
            } else {
                $processedLinks[] = $link;
            }
        }

        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $processedLinks[] = $this->productService->processImage($image);
            }
        }

        $data['drive_links'] = $processedLinks;
        $data['image_path'] = !empty($processedLinks) ? $processedLinks[0] : null;



        \Illuminate\Support\Facades\DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            $product->categories()->sync($request->category_ids);

            // Asegurar que exista la relación en la tabla stocks para cada almacén seleccionado con cantidad 0.00 si no existía
            foreach ($request->warehouse_ids as $warehouseId) {
                \App\Models\Stock::firstOrCreate([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                ], [
                    'quantity' => 0.00,
                    'inventory_value' => 0.00,
                    'average_cost' => 0.00,
                ]);
            }
        });

        return back()->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('success', 'Producto eliminado correctamente.');
    }

    public function downloadTemplate()
    {
        return Excel::download(new ProductTemplateExport, 'plantilla_productos.xlsx');
    }

    public function importPreview(Request $request, UnifiedImportService $importService): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            $path = $request->file('file')->store('imports', 'public');
            $fileName = $request->file('file')->getClientOriginalName();
            $preview = $importService->preview($path, (string) auth()->id());
            
            // Eliminar archivo inmediatamente después de procesar
            Storage::disk('public')->delete($path);
            
            return response()->json([
                'preview' => $preview,
                'file_name' => $fileName
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function import(Request $request, UnifiedImportService $importService): JsonResponse
    {
        $request->validate([
            'file_name' => 'required|string',
            'total_rows' => 'required|integer',
        ]);

        try {
            $log = $importService->initializeLog(
                $request->file_name,
                (int) $request->total_rows,
                (string) auth()->id()
            );

            return response()->json([
                'log_id' => $log->id,
                'message' => 'Proceso de importación inicializado.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function importChunk(Request $request, UnifiedImportService $importService): JsonResponse
    {
        $request->validate([
            'log_id' => 'required|exists:import_logs,id',
            'rows' => 'required|array',
            'override_prices' => 'required|boolean',
        ]);

        try {
            foreach ($request->rows as $row) {
                $importService->processRow(
                    $row,
                    (string) $request->log_id,
                    (bool) $request->override_prices
                );
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function importFinalize(Request $request, UnifiedImportService $importService): JsonResponse
    {
        $request->validate([
            'log_id' => 'required|exists:import_logs,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'provider_id' => 'required|exists:providers,id',
        ]);

        try {
            $metrics = $importService->completeImport(
                (string) $request->log_id,
                (string) $request->warehouse_id,
                (string) $request->provider_id,
                (string) auth()->id()
            );

            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'message' => 'Importación completada exitosamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function generateSku(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string',
            'category_ids' => 'required|array',
        ]);

        return response()->json([
            'code' => $this->productService->generateSku($request->name, $request->category_ids)
        ]);
    }

    public function uploadImage(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:5120',
        ]);

        try {
            $url = $this->productService->processImage($request->file('image'));
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function processImageUrl(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        try {
            $url = $this->productService->downloadAndConvertImage($request->url);
            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }


}
