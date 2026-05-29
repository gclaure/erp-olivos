<?php

declare(strict_types=1);

namespace App\Http\Resources\SuperAdmin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => $this->translatePermission($p->name)
                ];
            }),
            'users_count' => DB::table('model_has_roles')->where('role_id', $this->id)->count(),
        ];
    }

    private function translatePermission(string $name): string
    {
        $translations = [
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
            'manage-settings' => 'Gestionar Configuración',
            'manage-users' => 'Gestionar Usuarios',
            'manage-warehouses' => 'Gestionar Almacenes',
            'view-reports' => 'Ver Reportes',
            'manage-branches' => 'Administrar sucursales',
            'pos-access' => 'Acceso al POS',
            'manage-pos' => 'Gestionar Puntos de Venta',
            'manage-transfers' => 'Gestionar Transferencias',
            'manage-company' => 'Gestionar Datos de Empresa',
        ];

        return $translations[$name] ?? ucwords(str_replace(['-', '_'], ' ', $name));
    }
}
