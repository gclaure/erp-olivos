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
            'roles' => Role::whereNotIn('name', ['Super Admin', 'Super-admin'])
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
            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->with(['branch' => fn($q) => $q->withoutGlobalScopes()])
                ->orderBy('name')
                ->get()
                ->map(fn($w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'branch_id' => $w->branch_id,
                    'branch_name' => $w->branch->name ?? 'N/A',
                ]),
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
            'role' => 'nullable|string',
            'is_super_admin' => 'boolean',
            'branch_id' => 'required_without:is_super_admin|nullable|exists:branches,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_super_admin' => $request->is_super_admin,
            'branch_id' => $request->is_super_admin ? null : $request->branch_id,
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
            'role' => 'nullable|string',
            'is_super_admin' => 'boolean',
            'branch_id' => 'required_without:is_super_admin|nullable|exists:branches,id',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'is_super_admin' => $request->is_super_admin,
            'branch_id' => $request->is_super_admin ? null : $request->branch_id,
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
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
