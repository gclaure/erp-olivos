<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\PlanResource;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::all();

        return Inertia::render('SuperAdmin/Plans/Index', [
            'plans' => PlanResource::collection($plans)
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:plans,slug',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'billing_period' => 'required|in:MONTHLY,YEARLY',
            'is_active' => 'boolean',
            'features' => 'required|array',
            'features.max_users' => 'required|integer|min:-1',
            'features.max_branches' => 'required|integer|min:-1',
            'features.max_products' => 'required|integer|min:-1',
            'features.max_warehouses' => 'required|integer|min:-1',
            'features.max_pos' => 'required|integer|min:-1',
            'features.max_images_per_product' => 'required|integer|min:1',
            'features.has_catalog' => 'required|boolean',
        ]);

        Plan::create($validated);

        return back()->with('success', 'Plan creado exitosamente.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:100|unique:plans,slug,' . $plan->id,
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'billing_period' => 'required|in:MONTHLY,YEARLY',
            'is_active' => 'boolean',
            'features' => 'required|array',
            'features.max_users' => 'required|integer|min:-1',
            'features.max_branches' => 'required|integer|min:-1',
            'features.max_products' => 'required|integer|min:-1',
            'features.max_warehouses' => 'required|integer|min:-1',
            'features.max_pos' => 'required|integer|min:-1',
            'features.max_images_per_product' => 'required|integer|min:1',
            'features.has_catalog' => 'required|boolean',
        ]);

        $plan->update($validated);

        return back()->with('success', 'Plan actualizado exitosamente.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->companies()->exists()) {
            return back()->with('error', 'No se puede eliminar un plan que tiene empresas asociadas.');
        }

        $plan->delete();

        return back()->with('success', 'Plan eliminado exitosamente.');
    }

    public function toggleActive(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return back()->with('success', $plan->is_active ? 'Plan activado.' : 'Plan desactivado.');
    }
}
