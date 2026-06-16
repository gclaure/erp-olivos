<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\UpdateBranchRequest;
use App\Models\Branch;
use App\Facades\CompanyFacade;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BranchController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage-branches');

        $branches = Branch::withoutGlobalScope('branch_scoped')
            ->when($request->search, fn ($q) => $q->where('name', 'ilike', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Branches/Index', [
            'branches' => $branches,
            'filters' => $request->only(['search']),
            'canCreateBranch' => true,
        ]);
    }

    public function store(StoreBranchRequest $request, BranchService $service): RedirectResponse
    {
        Gate::authorize('manage-branches');

        $validated = $request->validated();
        $company = CompanyFacade::getCompany();
        
        if ($company === null) {
            return back()->with('error', 'No se ha configurado ninguna compañía en el sistema.');
        }

        $validated['company_id'] = $company->id;

        $service->createBranch($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Sucursal creada correctamente.');
    }

    public function update(UpdateBranchRequest $request, string $id, BranchService $service): RedirectResponse
    {
        Gate::authorize('manage-branches');

        $branch = Branch::withoutGlobalScope('branch_scoped')->findOrFail($id);

        $service->updateBranch($branch, $request->validated());

        return redirect()->route('admin.branches.index')
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    public function destroy(string $id): RedirectResponse
    {
        Gate::authorize('manage-branches');

        $branch = Branch::withoutGlobalScope('branch_scoped')->findOrFail($id);

        if ($branch->is_main) {
            return back()->with('error', 'No se puede desactivar la sucursal principal.');
        }

        // Check business rule: "No se puede desactivar una sucursal si tiene operaciones activas (ventas del día)."
        if (!\App\Facades\Branch::canDeactivate($id)) {
            return back()->with('error', 'No se puede desactivar: hay ventas registradas hoy en esta sucursal.');
        }

        // Rule: No se puede desactivar una sucursal si tiene almacenes activos.
        if ($branch->warehouses()->where('is_active', true)->exists()) {
            return back()->with('error', 'No se puede desactivar: la sucursal tiene almacenes activos.');
        }

        $branch->update(['is_active' => false]);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Sucursal desactivada correctamente.');
    }
}
