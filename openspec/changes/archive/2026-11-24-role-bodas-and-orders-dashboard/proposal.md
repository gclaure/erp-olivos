# Proposal

## Why

El negocio necesita segregar las operaciones del personal dedicado a eventos y reservas (rol `Bodas`), evitando que accedan a módulos sensibles como compras, proveedores, kardex, administración de usuarios y configuración. Este rol debe contar con un espacio de trabajo enfocado exclusivamente en la gestión de pedidos (`/admin/orders` y `/admin/orders/create`) y un Dashboard propio con indicadores operativos de reservas y entregas.

## What Changes

- **Registro del rol `Bodas`:** Creación e inicialización del rol `Bodas` asignando por defecto el permiso `manage-orders` mediante una migración y actualización en `RolesAndPermissionsSeeder`.
- **Dashboard especializado para Bodas:** Creación del componente Vue `resources/js/Pages/Admin/BodasDashboard.vue` y su lógica de negocio en `DashboardController` con métricas operativas (reservas activas, entregas de hoy, próximas entregas, reservas canceladas, pedidos recientes y accesos rápidos).
- **Protección estricta de rutas:** Aplicación del middleware `permission:manage-orders` a las rutas del grupo `orders` en `routes/admin.php`.
- **Menú lateral contextualizado:** Configuración en `SidebarService` para que los usuarios con rol `Bodas` visualicen únicamente el acceso a su Dashboard de Pedidos y el grupo "Pedidos" (*Registrar Pedido* e *Historial de Pedidos*), ocultando el resto de módulos del sistema.

## Capabilities

### New Capabilities
- `roles/bodas-role-access`: Definición del rol operativo `Bodas` con permiso predeterminado `manage-orders` y acceso restringido exclusivamente al módulo de pedidos y su panel analítico.
- `dashboard/bodas-orders-dashboard`: Panel de control (Dashboard) especializado con métricas operativas de reservas de pedidos y entregas para el rol `Bodas`.

### Modified Capabilities
<!-- None -->

## Impact

- **Backend:** `DashboardController.php`, `routes/admin.php`, `SidebarService.php`, `RolesAndPermissionsSeeder.php`, migración para crear el rol.
- **Frontend:** Nuevo componente `resources/js/Pages/Admin/BodasDashboard.vue`.
- **Base de datos:** Inserción limpia del rol `Bodas` vinculado a `manage-orders` con `guard_name = 'web'`.
