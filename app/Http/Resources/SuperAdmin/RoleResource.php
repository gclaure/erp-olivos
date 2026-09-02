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
            'create-consumption' => 'Registrar Consumo',
            'manage-consumption' => 'Gestionar Consumos',
            'manage-inventory'   => 'Gestionar Inventario',
            'manage-products'    => 'Gestionar Productos',
            'manage-categories'  => 'Gestionar Categorías',
            'manage-warehouses'  => 'Gestionar Almacenes',
            'create-purchases'   => 'Crear Compras',
            'manage-purchases'   => 'Gestionar Compras',
            'manage-providers'   => 'Gestionar Proveedores',
            'manage-users'       => 'Gestionar Usuarios',
            'manage-roles'       => 'Gestionar Roles',
            'manage-company'     => 'Gestionar Datos de Empresa',
            'manage-branches'    => 'Administrar Sucursales',
            'view-reports'       => 'Ver Reportes BI',
        ];

        return $translations[$name] ?? ucwords(str_replace(['-', '_'], ' ', $name));
    }
}
