<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use App\Models\Warehouse;
use App\Facades\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\MovementResource;
use App\Http\Resources\MovementDetailResource;

class MovementController extends Controller
{
    public function index(Request $request): Response
    {
        $activeBranch = Branch::getActiveBranch();
        
        if (!$activeBranch) {
            return Inertia::render('Admin/Movement/Index', [
                'movements' => [],
                'warehouses' => [],
                'filters' => $request->all(['search', 'warehouse_id']),
            ]);
        }

        $search = $request->input('search');
        $warehouseId = $request->input('warehouse_id');

        $movements = Movement::query()
            ->with(['warehouse', 'user'])
            ->whereHas('warehouse', function($q) use ($activeBranch) {
                $q->where('branch_id', $activeBranch->id);
            })
            ->when($search, fn ($q) => $q->where('reason', 'ilike', "%{$search}%"))
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $warehouses = Warehouse::where('branch_id', $activeBranch->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Movement/Index', [
            'movements' => MovementResource::collection($movements),
            'warehouses' => $warehouses,
            'filters' => [
                'search' => $search,
                'warehouse_id' => $warehouseId,
            ],
        ]);
    }

    public function show(Movement $movement): JsonResponse
    {
        $movement->load(['details.product', 'warehouse', 'user']);
        
        return response()->json([
            'data' => new MovementDetailResource($movement)
        ]);
    }
    public function create(): Response
    {
        $activeBranch = Branch::getActiveBranch();
        
        $warehouses = Warehouse::where('branch_id', $activeBranch->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Movement/Create', [
            'warehouses' => $warehouses,
            'types' => [
                ['label' => 'Entrada', 'value' => 'entrada'],
                ['label' => 'Salida', 'value' => 'salida'],
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'type' => 'required|in:entrada,salida',
            'date' => 'required|date',
            'reason' => 'required|string|min:3|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        try {
            $service = app(\App\Services\MovementService::class);
            
            $data = [
                'warehouse_id' => $validated['warehouse_id'],
                'type' => $validated['type'],
                'date' => $validated['date'],
                'reason' => $validated['reason'],
                'notes' => $validated['notes'],
            ];

            $items = collect($validated['items'])->map(fn($item) => [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ])->toArray();

            $service->recordMovement($data, $items);

            return redirect()->route('admin.movements.index')
                ->with('success', 'Movimiento registrado correctamente.');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $warehouseId = $request->get('warehouse_id');

        $products = \App\Models\Product::query()
            ->where('is_active', true)
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            }, function ($q) {
                $q->latest();
            })
            ->limit(10)
            ->get();

        $data = $products->map(function ($product) use ($warehouseId) {
            $stock = 0;
            if ($warehouseId) {
                $stockRecord = \App\Models\Stock::where('product_id', $product->id)
                    ->where('warehouse_id', $warehouseId)
                    ->first();
                $stock = $stockRecord ? (float) $stockRecord->quantity : 0;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'stock' => $stock,
            ];
        });

        return response()->json($data);
    }
}
