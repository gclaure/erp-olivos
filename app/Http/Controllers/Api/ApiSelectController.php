<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Provider;
use App\Models\Category;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiSelectController extends Controller
{
    public function warehouses(Request $request): JsonResponse
    {
        $search = $request->get('search');
        
        $data = Warehouse::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->when($search, fn($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->name,
            ]);

        return response()->json($data);
    }

    public function providers(Request $request): JsonResponse
    {
        $search = $request->get('search');
        
        $data = Provider::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->when($search, fn($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ]);

        return response()->json($data);
    }

    public function categories(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $selected = $request->get('selected', []);

        $data = Category::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->when($search, fn($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->when($selected, fn($q) => $q->orWhereIn('id', (array)$selected))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($data);
    }

    public function unitOfMeasures(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $selected = $request->get('selected');

        $data = UnitOfMeasure::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->when($search, fn($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->when($selected, fn($q) => $q->orWhere('id', $selected))
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json($data);
    }

    public function products(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $type = $request->get('type');
        $warehouseId = $request->get('warehouse_id');
        
        $data = \App\Models\Product::query()
            ->select(['id', 'type', 'name', 'code', 'price', 'has_expiration', 'units_per_package', 'package_name', 'unit_of_measure_id'])
            ->with(['unitOfMeasure:id,abbreviation', 'stocks.warehouse:id,name'])
            ->withSum(['stocks as current_stock' => function($q) use ($warehouseId) {
                if ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
            }], 'quantity')
            ->selectSub(function ($query) {
                $query->from('purchase_details')
                    ->join('purchases', 'purchases.id', '=', 'purchase_details.purchase_id')
                    ->whereColumn('purchase_details.product_id', 'products.id')
                    ->where('purchases.status', '!=', 'anulada')
                    ->orderByDesc('purchases.date')
                    ->orderByDesc('purchase_details.created_at')
                    ->select('purchase_details.unit_price')
                    ->limit(1);
            }, 'last_purchase_price')
            ->where('is_active', true)
            ->when($type, fn($q, $t) => $q->where('type', $t))
            ->when($search, function($q, $search) {
                $q->where(fn($subQ) =>
                    $subQ->where('name', 'ilike', "%{$search}%")
                         ->orWhere('code', 'ilike', "%{$search}%")
                );
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'type' => $p->type?->value ?? (string)$p->type,
                'is_inventoriable' => $p->isInventoriable(),
                'price' => (float)($p->last_purchase_price ?? $p->price ?? 0),
                'last_purchase_price' => (float)($p->last_purchase_price ?? 0),
                'catalog_price' => (float)$p->price,
                'stock' => (float)($p->current_stock ?? 0),
                'has_expiration' => (bool)$p->has_expiration,
                'units_per_package' => (float)$p->units_per_package,
                'package_name' => $p->package_name,
                'unit' => $p->unitOfMeasure?->abbreviation ?? 'UND',
                'warehouses' => $p->stocks->map(fn($s) => $s->warehouse->name ?? null)->filter()->unique()->values()->all(),
            ]);

        return response()->json($data);
    }
}
