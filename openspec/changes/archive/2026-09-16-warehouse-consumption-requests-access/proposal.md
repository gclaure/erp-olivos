## Why

El personal con rol **Almacén** (`almacen@gmail.com`) es el responsable operativo de recibir los pedidos de consumo interno, preparar el stock disponible, reportar faltantes y realizar la entrega física. Actualmente, este usuario no puede acceder al listado de solicitudes (`admin.consumption-requests.index`) desde la interfaz web porque el menú lateral "Consumos" se oculta completamente debido a que el rol carece del permiso `manage-consumption` en la base de datos. 

Además, es imperativo asegurar que toda actualización de permisos y datos se realice mediante una **migración oficial de Laravel**, sin alterar ni romper los flujos existentes de otros roles (Consumidores y Administradores).

## What Changes

- **Migración de Sincronización de Permisos**: Se crea una nueva migración de base de datos en Laravel que asigna formalmente al rol `Almacén` el permiso `manage-consumption` (junto con sus permisos operacionales asociados de inventario y compras estipulados en el diseño del ERP).
- **Visibilidad en Menú Lateral (`SidebarService.php`)**: Se garantiza que el ítem "Consumos Solicitados" sea plenamente visible para usuarios con rol `Almacén`, manteniendo oculto "Registrar Consumo" (ya que almacén solo gestiona y despacha).
- **Consistencia en Controladores y Vistas**: Se valida que `ConsumptionRequestController::index` y `Show.vue` permitan a Almacén visualizar el listado completo, filtrar y gestionar despachos conforme a sus almacenes asignados sin interferencias.

## Capabilities

### New Capabilities
- `warehouse-consumption-menu-access`: Permite al usuario con rol de Almacén visualizar y acceder a la lista de solicitudes de consumo interno mediante permisos asignados por migración.

### Modified Capabilities
<!-- No modified capabilities -->

## Impact

- **Base de Datos**: Nueva migración en `database/migrations/` para sincronizar permisos con Spatie Permission sin tocar queries directas manuales.
- **Backend**: `app/Services/SidebarService.php` y `app/Http/Controllers/Admin/ConsumptionRequestController.php`.
- **Frontend**: Menú de navegación lateral responsivo (`AdminLayout` y `SidebarService`).
