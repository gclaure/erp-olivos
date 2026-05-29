<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search');

        $providers = Provider::query()
            ->when($search, function ($q, $search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('contact_name', 'ilike', "%{$search}%")
                    ->orWhere('document_number', 'ilike', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Provider/Index', [
            'providers' => ProviderResource::collection($providers),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(SaveProviderRequest $request): RedirectResponse
    {
        Provider::create($request->validated());

        return back()->with('success', 'Proveedor creado correctamente.');
    }

    public function update(SaveProviderRequest $request, Provider $provider): RedirectResponse
    {
        $provider->update($request->validated());

        return back()->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        if ($provider->purchases()->exists() || $provider->purchaseOrders()->exists()) {
            return back()->with('error', 'No se puede eliminar: el proveedor tiene transacciones asociadas.');
        }

        $provider->delete();

        return back()->with('success', 'Proveedor eliminado correctamente.');
    }
}
