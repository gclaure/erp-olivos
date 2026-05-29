<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\AsesorResource;
use App\Models\Asesor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AsesorController extends Controller
{
    public function index(Request $request): Response
    {
        $asesores = Asesor::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('email', 'ilike', '%' . $request->search . '%');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Asesores/Index', [
            'asesores' => AsesorResource::collection($asesores),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        Asesor::create($validated);

        return back()->with('success', 'Asesor registrado correctamente.');
    }

    public function update(Request $request, Asesor $asesore): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $asesore->update($validated);

        return back()->with('success', 'Asesor actualizado correctamente.');
    }

    public function destroy(Asesor $asesore): RedirectResponse
    {
        $asesore->delete();

        return back()->with('success', 'Asesor eliminado correctamente.');
    }
}
