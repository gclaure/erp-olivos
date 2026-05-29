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
        
        $data = \App\Models\Product::query()
            ->select(['id', 'name', 'code', 'price', 'has_expiration', 'units_per_package', 'package_name', 'unit_of_measure_id'])
            ->with(['unitOfMeasure:id,abbreviation', 'stocks.warehouse:id,name'])
            ->where('is_active', true)
            ->when($search, function($q, $search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'price' => (float)$p->price,
                'has_expiration' => (bool)$p->has_expiration,
                'units_per_package' => (float)$p->units_per_package,
                'package_name' => $p->package_name,
                'unit' => $p->unitOfMeasure?->abbreviation ?? 'UND',
                'warehouses' => $p->stocks->map(fn($s) => $s->warehouse->name ?? null)->filter()->unique()->values()->all(),
            ]);

        return response()->json($data);
    }
}
