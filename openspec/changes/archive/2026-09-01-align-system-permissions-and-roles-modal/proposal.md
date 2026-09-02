## Why

El modal de "Editar Rol" y la lista de permisos del sistema aún conservan permisos y descripciones heredados de una versión anterior con módulos de ventas comerciales, clientes y POS comercial (como `create-sales`, `manage-sales`, `manage-clients`, `manage-deliveries`). Esto genera confusión para los administradores al asignar accesos y desacopla la seguridad del menú real del ERP (orientado a Inventario, Consumo Interno, Compras y Administración). Además, el rol "Consumidor" carecía de permisos formales en base de datos.

## What Changes

- Se transforma y renombra el permiso `create-sales` ("Crear Ventas") a `create-consumption` ("Registrar Consumo"), describiendo con claridad la capacidad de solicitar insumos desde el catálogo de consumo.
- Se introduce `manage-consumption` ("Gestionar Consumos") para autorizar la visualización y despacho de solicitudes de consumo interno.
- Se depuran o archivan los permisos obsoletos que ya no tienen pantallas asociadas (`manage-clients`, `manage-deliveries`, `manage-pos`, `pos-access`).
- Se actualizan las traducciones y descripciones de los permisos en `RoleController.php`, `RoleResource.php` y las vistas Vue ([Roles/Index.vue](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/js/Pages/SuperAdmin/Roles/Index.vue) y `AddRoleModal.vue`).
- Se asigna de forma formal el permiso `create-consumption` al rol `Consumidor` y `manage-consumption` a los roles `Almacén` y `Administrador`.
- Se simplifica la lógica de verificación en `SidebarService.php` para basarse en `$user->can(...)` de Spatie Permissions.

## Capabilities

### New Capabilities
- `erp-permissions-alignment`: Alineación y depuración de la matriz de roles y permisos con los módulos activos del sistema (Inventario, Consumos, Compras, Administración).

### Modified Capabilities
<!-- Sin modificaciones en specs de requerimientos funcionales previos -->

## Impact

- Base de Datos: Actualización / migración de registros en la tabla `permissions` y asignaciones en `role_has_permissions`.
- Backend:
  - `app/Http/Controllers/SuperAdmin/RoleController.php`
  - `app/Http/Resources/SuperAdmin/RoleResource.php`
  - `app/Services/SidebarService.php`
- Frontend:
  - `resources/js/Pages/SuperAdmin/Roles/Index.vue`
  - `resources/js/Pages/SuperAdmin/Tenants/Partials/AddRoleModal.vue`
