<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class SidebarService
{
    public function __construct()
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItems(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $items = $this->buildMenu();
        return $this->filterByPermissions($items, $user);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMenu(): array
    {
        return [
            [
                'label' => 'Dashboard',
                'icon' => 'home',
                'route' => 'admin.dashboard',
                'permission' => null,
            ],
            [
                'label' => 'Inventario',
                'icon' => 'cube',
                'permission' => 'manage-inventory',
                'children' => [
                    [
                        'label' => 'Productos',
                        'icon' => 'shopping-bag',
                        'route' => 'admin.products.index',
                        'permission' => 'manage-products',
                    ],
                    [
                        'label' => 'Categorías',
                        'icon' => 'tag',
                        'route' => 'admin.categories.index',
                        'permission' => 'manage-categories',
                    ],
                    [
                        'label' => 'Unidades de Medida',
                        'icon' => 'swatch',
                        'route' => 'admin.unit-of-measures.index',
                        'permission' => 'manage-products',
                    ],
                    [
                        'label' => 'Almacenes',
                        'icon' => 'home-modern',
                        'route' => 'admin.warehouses.index',
                        'permission' => 'manage-warehouses',
                    ],
                    [
                        'label' => 'Movimientos (Kardex)',
                        'icon' => 'book-open',
                        'route' => 'admin.kardex.index',
                        'permission' => 'manage-inventory',
                    ],
                    [
                        'label' => 'Ajustes Manuales (Mermas)',
                        'icon' => 'adjustments-horizontal',
                        'route' => 'admin.movements.index',
                        'permission' => 'manage-inventory',
                    ],

                    [
                        'label' => 'Registrar Consumo',
                        'icon' => 'computer-desktop',
                        'route' => 'admin.consumption-requests.create',
                        'permission' => 'manage-inventory',
                    ],
                    [
                        'label' => 'Consumos Solicitados',
                        'icon' => 'clipboard-document-list',
                        'route' => 'admin.consumption-requests.index',
                        'permission' => 'manage-inventory',
                    ],
                ]
            ],
            [
                'label' => 'Compras',
                'icon' => 'shopping-cart',
                'permission' => 'manage-purchases',
                'children' => [
                    [
                        'label' => 'Solicitudes de Compra',
                        'icon' => 'document-text',
                        'route' => 'admin.purchase-orders.index',
                        'permission' => 'manage-purchases',
                    ],
                    [
                        'label' => 'Registrar Compra',
                        'icon' => 'plus-circle',
                        'route' => 'admin.purchases.create',
                        'permission' => 'manage-purchases',
                    ],
                    [
                        'label' => 'Historial de Compras',
                        'icon' => 'clipboard-document-list',
                        'route' => 'admin.purchases.index',
                        'permission' => 'manage-purchases',
                    ],
                    [
                        'label' => 'Proveedores',
                        'icon' => 'truck',
                        'route' => 'admin.providers.index',
                        'permission' => 'manage-providers',
                    ],
                    [
                        'label' => 'Cuentas por Pagar',
                        'icon' => 'document-text',
                        'route' => 'admin.accounts-payable.index',
                        'permission' => 'manage-purchases',
                    ],
                ]
            ],


            [
                'header' => 'Administración',
            ],
            [
                'label' => 'Usuarios',
                'icon' => 'users',
                'super_admin_only' => true,
                'children' => [
                    [
                        'label' => 'Lista de Usuarios',
                        'icon' => 'users',
                        'route' => 'admin.users.index',
                        'permission' => 'manage-users',
                    ],
                    [
                        'label' => 'Roles y Permisos',
                        'icon' => 'identification',
                        'route' => 'admin.roles.index',
                        'super_admin_only' => true,
                    ],
                ]
            ],
            [
                'label' => 'Configuración',
                'icon' => 'cog-6-tooth',
                'permission' => 'manage-company',
                'children' => [
                    [
                        'label' => 'Empresa',
                        'icon' => 'building-office',
                        'route' => 'admin.company.edit',
                        'permission' => 'manage-company',
                    ],
                    [
                        'label' => 'Sucursales',
                        'icon' => 'map-pin',
                        'route' => 'admin.branches.index',
                        'permission' => 'manage-branches',
                    ],
                ]
            ],
            [
                'label' => 'Reportes BI',
                'icon' => 'chart-pie',
                'route' => 'admin.reports.index',
                'permission' => 'view-reports',
            ],
        ];
    }


    private function filterByPermissions(array $items, mixed $user): array
    {
        $filtered = [];

        foreach ($items as $item) {
            // Bloquear acceso del menú "Registrar Consumo" al rol Almacén
            if (isset($item['route']) && $item['route'] === 'admin.consumption-requests.create' && $user->hasRole(['Almacén', 'almacen'])) {
                continue;
            }

            // Filtrar si es exclusivo de Super Admin o Administrador
            $isSuperAdmin = $user->is_super_admin;
            $isAdmin = $user->hasRole(['Admin', 'Administrador', 'admin', 'administrador']);

            if (isset($item['super_admin_only']) && $item['super_admin_only'] && !$isSuperAdmin && !$isAdmin) {
                continue;
            }

            if (isset($item['header'])) {
                $filtered[] = $item;
                continue;
            }

            if (isset($item['children'])) {
                $item['children'] = $this->filterByPermissions($item['children'], $user);
                if (count($item['children']) > 0) {
                    $filtered[] = $item;
                }
                continue;
            }

            // Permitir que el Consumidor visualice "Registrar Consumo" y "Consumos Solicitados" en el sidebar
            if (isset($item['route']) && 
                in_array($item['route'], ['admin.consumption-requests.create', 'admin.consumption-requests.index']) && 
                $user->hasRole(['Consumidor', 'consumidor'])) {
                $filtered[] = $item;
                continue;
            }

            if (!isset($item['permission']) || $item['permission'] === null || $user->can($item['permission'])) {
                $filtered[] = $item;
            }
        }

        // Second pass: remove orphaned headers
        $finalItems = [];
        $headerPending = null;

        foreach ($filtered as $item) {
            if (isset($item['header'])) {
                $headerPending = $item;
                continue;
            }

            if ($headerPending !== null) {
                $finalItems[] = $headerPending;
                $headerPending = null;
            }
            
            $finalItems[] = $item;
        }

        return $finalItems;
    }
}
