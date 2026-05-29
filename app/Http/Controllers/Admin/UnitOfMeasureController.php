<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUnitOfMeasureRequest;
use App\Http\Requests\Admin\UpdateUnitOfMeasureRequest;
use App\Http\Resources\UnitOfMeasureResource;
use App\Models\UnitOfMeasure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitOfMeasureController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $units = UnitOfMeasure::query()
            ->select(['id', 'name', 'abbreviation', 'is_active', 'created_at'])
            ->withCount('products')
            ->when($search, function ($q) use ($search) {
                return $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('abbreviation', 'ilike', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/UnitOfMeasure/Index', [
            'unitOfMeasures' => UnitOfMeasureResource::collection($units),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(StoreUnitOfMeasureRequest $request): RedirectResponse
    {
        UnitOfMeasure::create($request->validated());

        return back()->with('success', 'Unidad de medida creada correctamente.');
    }

    public function update(UpdateUnitOfMeasureRequest $request, UnitOfMeasure $unit_of_measure): RedirectResponse
    {
        $unit_of_measure->update($request->validated());

        return back()->with('success', 'Unidad de medida actualizada correctamente.');
    }

    public function destroy(UnitOfMeasure $unit_of_measure): RedirectResponse
    {
        if ($unit_of_measure->products()->exists()) {
            return back()->with('error', 'No se puede eliminar: la unidad tiene productos asociados.');
        }

        $unit_of_measure->delete();

        return back()->with('success', 'Unidad de medida eliminada correctamente.');
    }
}
