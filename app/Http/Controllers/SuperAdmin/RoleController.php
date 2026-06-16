<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SuperAdmin\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    private array $permissionTranslations = [
        'create-purchases' => 'Crear Compras',
        'create-sales' => 'Crear Ventas',
        'manage-categories' => 'Gestionar Categorías',
        'manage-clients' => 'Gestionar Clientes',
        'manage-inventory' => 'Gestionar Inventario',
        'manage-products' => 'Gestionar Productos',
        'manage-providers' => 'Gestionar Proveedores',
        'manage-purchases' => 'Gestionar Compras',
        'manage-roles' => 'Gestionar Roles',
        'manage-sales' => 'Gestionar Ventas',
        'manage-deliveries' => 'Gestionar Entregas',
        'manage-settings' => 'Gestionar Configuración',
        'manage-users' => 'Gestionar Usuarios',
        'manage-warehouses' => 'Gestionar Almacenes',
        'view-reports' => 'Ver Reportes',
        'manage-branches' => 'Administrar sucursales',
        'pos-access' => 'Acceso a Puntos de Venta',
        'manage-pos' => 'Gestionar Puntos de Venta',
        'manage-transfers' => 'Gestionar Transferencias',
        'manage-company' => 'Gestionar Datos de Empresa',
        'pos-delivery-pickup' => 'Habilitar Retiro en Local',
        'pos-delivery-home' => 'Habilitar Envío a Domicilio',
        'pos-delivery-point' => 'Habilitar Punto de Encuentro',
        'pos-delivery-package' => 'Habilitar Envío por Encomienda',
    ];

    public function index(Request $request): Response
    {
        $roles = Role::query()
            ->with('permissions')
            ->whereNotIn('name', [
                'Super Admin', 
                'Super-admin', 
                'Super Administrador', 
                'super-admin', 
                'super administrador', 
                'Superadmin'
            ])
            ->when($request->search, fn ($q) => $q->where('name', 'ilike', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $permissions = Permission::query()
            ->when($request->search && $request->tab === 'permissions', fn ($q) => $q->where('name', 'ilike', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Roles/Index', [
            'roles' => RoleResource::collection($roles),
            'permissions' => $permissions,
            'allPermissions' => Permission::orderBy('name')->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => $this->translatePermission($p->name)
                ];
            }),
            'filters' => $request->only(['search', 'tab']),
            'translations' => $this->permissionTranslations
        ]);
    }

    private function translatePermission(string $name): string
    {
        return $this->permissionTranslations[$name] ?? ucwords(str_replace(['-', '_'], ' ', $name));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$request->user()->is_super_admin) {
            return back()->with('error', 'Solo el Super Administrador puede crear nuevos roles.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'permissions' => 'array'
        ]);

        // Validar unicidad
        $exists = Role::where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['name' => 'Ya existe un rol con este nombre en tu empresa.']);
        }

        $role = Role::create([
            'name' => $validated['name'], 
            'guard_name' => 'web',
        ]);
        
        if (in_array(strtolower($role->name), ['admin', 'administrador'])) {
            $role->syncPermissions(Permission::all());
        } else if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return back()->with('success', 'Rol creado correctamente.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'permissions' => 'array'
        ];

        // Solo Super Admin puede cambiar el nombre del rol
        if ($user->is_super_admin) {
            $rules['name'] = [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')
                    ->ignore($role->id)
            ];
        }

        $validated = $request->validate($rules);

        if ($user->is_super_admin && isset($validated['name'])) {
            $role->update(['name' => $validated['name']]);
        }

        if (in_array(strtolower($role->name), ['admin', 'administrador'])) {
            $role->syncPermissions(Permission::all());
        } else {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        return back()->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (!auth()->user()->is_super_admin) {
            return back()->with('error', 'Solo el Super Administrador puede eliminar roles.');
        }

        if (in_array($role->name, ['admin', 'Administrador'])) {
            return back()->with('error', 'No se puede eliminar el rol de administrador.');
        }

        $role->delete();
        return back()->with('success', 'Rol eliminado correctamente.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name'
        ]);

        Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        return back()->with('success', 'Permiso creado correctamente.');
    }

    public function updatePermission(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('permissions', 'name')->ignore($permission->id)
            ]
        ]);

        $permission->update(['name' => $validated['name']]);

        return back()->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        $permission->delete();
        return back()->with('success', 'Permiso eliminado correctamente.');
    }
}
