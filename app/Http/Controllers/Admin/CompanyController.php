<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Facades\CompanyFacade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    /**
     * Display the company edit form.
     */
    public function edit(): Response
    {
        Gate::authorize('manage-company');

        $company = CompanyFacade::getCompany();

        return Inertia::render('Admin/Settings/Company', [
            'company' => $company ? [
                'nit' => $company->nit,
                'name' => $company->name,
                'business_name' => $company->business_name,
                'phone' => $company->phone ?? '',
                'email' => $company->email ?? '',
                'logo_url' => $company->logo_url,
                'show_name' => (bool) $company->show_name,
                'inventory_method' => $company->inventory_method->value,
                'has_inventory_movements' => (bool) $company->has_inventory_movements,
                'inventories_closed_until' => $company->inventories_closed_until?->format('Y-m-d'),
            ] : null,
        ]);
    }

    /**
     * Update the company information.
     */
    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        // El request ya maneja la autorización en su método authorize()
        
        $data = $request->validated();

        CompanyFacade::update($data);

        // Clear cache to reflect changes
        Cache::flush();

        return redirect()->back()->with('success', 'La información de la empresa ha sido actualizada correctamente.');
    }
}
