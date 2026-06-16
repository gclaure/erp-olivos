<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Facades\Branch as BranchFacade;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->where('is_super_admin', false)
            ->with(['roles', 'branch'])
            ->when($request->search, function ($q, $search) {
                $q->where(fn($subQuery) => 
                    $subQuery->where('name', 'ilike', "%{$search}%")
                             ->orWhere('email', 'ilike', "%{$search}%")
                );
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($users),
            'filters' => $request->only(['search']),
            'roles' => Role::whereNotIn('name', ['Super Admin', 'Super-admin', 'Super Administrador', 'Super-administrador'])
                 ->orderBy('name')
                 ->get()
                 ->map(fn($r) => ['label' => ucfirst($r->name), 'value' => $r->name]),
            'branches' => Branch::query()
                ->when(auth()->user()?->is_super_admin, function($q) {
                    return $q->withoutGlobalScopes();
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'allPointsOfSale' => [],
            'canCreateUser' => true,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string',
            'is_super_admin' => 'boolean',
            'branch_id' => 'required_unless:is_super_admin,true,1|nullable|exists:branches,id',
            'area' => 'required_if:role,Consumidor,CONSUMIDOR,consumidor|nullable|string|in:Cocina,Pastelería,Eventos|max:150',
        ], [
            'role.required' => 'El rol administrativo es obligatorio.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.required_unless' => 'La sucursal es obligatoria.',
            'area.required_if' => 'El campo área es obligatorio cuando el rol es Consumidor.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_super_admin' => $request->is_super_admin,
            'branch_id' => $request->is_super_admin ? null : $request->branch_id,
            'area' => (isset($request->role) && strcasecmp($request->role, 'Consumidor') === 0) ? $request->area : null,
        ];

        // Security check for non-super admins
        if (!auth()->user()?->is_super_admin && !$request->is_super_admin) {
            $data['branch_id'] = BranchFacade::getActiveBranchId();
        }

        $user = User::create($data);

        if ($request->role) {
            $user->syncRoles([$request->role]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => "required|email|max:150|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:8',
            'role' => 'required|string',
            'is_super_admin' => 'boolean',
            'branch_id' => 'required_unless:is_super_admin,true,1|nullable|exists:branches,id',
            'area' => 'required_if:role,Consumidor,CONSUMIDOR,consumidor|nullable|string|in:Cocina,Pastelería,Eventos|max:150',
        ], [
            'role.required' => 'El rol administrativo es obligatorio.',
            'branch_id.required' => 'La sucursal es obligatoria.',
            'branch_id.required_unless' => 'La sucursal es obligatoria.',
            'area.required_if' => 'El campo área es obligatorio cuando el rol es Consumidor.',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_super_admin' => $request->is_super_admin,
            'branch_id' => $request->is_super_admin ? null : $request->branch_id,
            'area' => (isset($request->role) && strcasecmp($request->role, 'Consumidor') === 0) ? $request->area : null,
        ];

        if (!auth()->user()?->is_super_admin && !$request->is_super_admin) {
            $data['branch_id'] = BranchFacade::getActiveBranchId();
        }

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->role) {
            $user->syncRoles([$request->role]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['is_active' => false]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario desactivado correctamente.');
    }

    /**
     * Alternar el estado activo/inactivo del colaborador.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes cambiar el estado de tu propia cuenta.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $statusMessage = $user->is_active ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';

        return redirect()->route('admin.users.index')
            ->with('success', $statusMessage);
    }
}
