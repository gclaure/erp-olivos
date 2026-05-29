<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWarehouseRequest;
use App\Http\Requests\Admin\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $warehouses = Warehouse::query()
            ->with(['branch'])
            ->when($search, fn ($q) => $q->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Admin/Warehouse/Index', [
            'warehouses' => WarehouseResource::collection($warehouses),
            'branches' => $branches->map(fn($b) => [
                'value' => $b->id,
                'label' => $b->name
            ]),
            'filters' => [
                'search' => $search,
            ],
            'activeBranchId' => \App\Facades\Branch::getActiveBranchId(),
        ]);
    }

    public function store(StoreWarehouseRequest $request, WarehouseService $service): RedirectResponse
    {
        $service->create($request->validated());

        return back()->with('success', 'Almacén creado correctamente.');
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return back()->with('success', 'Almacén actualizado correctamente.');
    }

    public function destroy(Warehouse $warehouse, WarehouseService $service): RedirectResponse
    {
        if (!$service->canDeactivate($warehouse->id)) {
            return back()->with('error', 'No se puede desactivar: el almacén tiene stock registrado.');
        }

        $warehouse->update(['is_active' => false]);

        return back()->with('success', 'Almacén desactivado correctamente.');
    }
}
