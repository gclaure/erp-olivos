# Tasks

## 1. Backend: Simplificación de Métricas en DashboardController

- [x] 1.1 Refactorizar `DashboardController.php` para eliminar consultas logísticas de entrega (`getBodasTodayDeliveries`, `getBodasUpcomingDeliveries`) y suministrar KPIs de registro (`registered_today`, `total_orders`, `active_orders`, `cancelled_orders`) junto con el listado ampliado de `recentOrders`.

## 2. Frontend: Rediseño de BodasDashboard.vue

- [x] 2.1 Reestructurar `resources/js/Pages/Admin/BodasDashboard.vue` eliminando las tarjetas y secciones de entregas y despachos.
- [x] 2.2 Diseñar la tabla principal de Últimos Pedidos Registrados con columnas de Folio, Cliente, Teléfono, Fecha de Registro, Fecha Solicitada, Productos, Estado y botones de acción rápida.
- [x] 2.3 Compilar assets con `npm run build` y verificar que no existan errores de sintaxis en Vue ni Tailwind.
