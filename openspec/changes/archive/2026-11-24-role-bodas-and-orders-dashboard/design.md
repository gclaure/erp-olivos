# Design

## Context

El sistema gestiona roles y permisos a través de `spatie/laravel-permission`. En el `DashboardController`, la ruta `/admin` ya evalúa la pertenencia a roles (`$isAdmin`, `$isConsumer`, `$isWarehouse`) para bifurcar hacia vistas especializadas (`Admin/ConsumerDashboard` o el dashboard administrativo global).
El módulo de pedidos (`OrderController`) contiene la gestión completa de reservas (`/admin/orders` y `/admin/orders/create`), pero requiere que su ruta esté protegida mediante el middleware de permisos `permission:manage-orders` para garantizar que usuarios no autorizados no accedan.

## Goals / Non-Goals

**Goals:**
- Registrar el rol `Bodas` asociándole el permiso `manage-orders` por defecto mediante migración limpia y seeder.
- Proteger las rutas `/admin/orders/*` con el middleware `permission:manage-orders`.
- En `DashboardController`, detectar `$isBodas` (usuario con rol `Bodas` y no admin) para servir métricas operativas de pedidos y renderizar `Admin/BodasDashboard`.
- Construir `BodasDashboard.vue` con Tailwind CSS y Vue 3 (Composition API, `<script setup>`), completamente responsive y adaptado a dark mode, mostrando KPIs de pedidos, accesos rápidos y lista de entregas urgentes/próximas.
- En `SidebarService`, asegurar que un usuario con rol `Bodas` solo reciba los nodos de navegación correspondientes a su Dashboard y a Pedidos.

**Non-Goals:**
- No se modifican los modelos ni las tablas de pedidos existentes (`orders`, `order_items`, etc.).
- No se otorgan permisos de administración de usuarios, compras ni finanzas a este rol.

## Decisions

### 1. Manejo del Dashboard dedicado en DashboardController
- **Decisión:** Agregar en `DashboardController@index` la condición `$isBodas = $user->hasRole(['Bodas', 'bodas']) && !$isAdmin;`. Si es verdadero, se calcula un DTO/array con las estadísticas operativas de pedidos (`getBodasStats`, `getUpcomingDeliveries`, `getRecentOrders`) y se devuelve `Inertia::render('Admin/BodasDashboard', [...])`.
- **Alternativa descartada:** Crear una ruta separada `/admin/bodas-dashboard`. Se descartó para mantener consistencia con `ConsumerDashboard`, donde la raíz administrativa `/admin` atiende de forma transparente según el perfil del usuario.

### 2. Creación del rol vía migración idempotente y Seeder
- **Decisión:** Crear una migración que verifique la existencia del rol `Bodas` con `guard_name = 'web'` y le asigne `manage-orders` con `PermissionRegistrar::forgetCachedPermissions()`, además de actualizar `RolesAndPermissionsSeeder.php`.
- **Razón:** Garantiza que se aplique automáticamente sin necesidad de scripts manuales y sin violar la regla de no ejecutar queries directas no versionadas.

### 3. Componente BodasDashboard en Vue 3
- **Decisión:** Diseñar una interfaz limpia, responsiva y con modo oscuro que muestre:
  - Header con saludo y accesos rápidos (Botón "Registrar Pedido" y "Ver Historial").
  - 4 tarjetas KPI: Total Reservas Activas, Entregas Hoy, Entregas Próximas (próximos 7 días), Reservas Anuladas.
  - Tabla de Entregas del Día / Próximas con estado y enlaces al detalle.
- **Alternativa descartada:** Reutilizar el dashboard de consumidor; descartado porque el consumidor solo ve requisiciones de consumo de insumos internos, mientras Bodas gestiona pedidos comerciales externos de pastelería/catering.

## Risks / Trade-offs

- **[Riesgo: Usuario con rol Bodas intenta acceder a rutas protegidas de inventario o compras]** → **Mitigación:** Las rutas en `routes/admin.php` ya exigen permisos específicos (`manage-inventory`, `manage-purchases`) o middleware de rol admin; al no tenerlos, Laravel aborta con 403 automáticamente.
- **[Riesgo: El usuario tiene múltiples roles asignados]** → **Mitigación:** La comprobación prioriza Administrador (`$isAdmin`). Si un usuario es Administrador y además tiene Bodas, mantiene la vista de Administrador global.
