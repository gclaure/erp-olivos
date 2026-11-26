# Design

## Context

El rol `Bodas` es una posición de captura y consulta de reservas de pedidos. La implementación inicial asumió erróneamente un rol logístico de entregas y despachos ("Entregas de hoy", "Próximos 7 días"). El usuario ha indicado expresamente que este rol únicamente registra pedidos y consulta la lista.

## Goals / Non-Goals

**Goals:**
- Ajustar `DashboardController.php` para eliminar consultas innecesarias de despacho (`todayDeliveries`, `upcomingDeliveries`) y suministrar:
  - `stats`: `registered_today`, `total_month`, `active_month`, `cancelled_month`.
  - `recentOrders`: listado ampliado de los últimos 10 a 15 pedidos tomados, incluyendo cliente, teléfono, fecha de registro, fecha solicitada de entrega, resumen de productos, estado y ID para acciones.
- Rediseñar `BodasDashboard.vue` para que la sección principal sea una tabla limpia de pedidos recientes con paginación o enlace directo al historial, eliminando cualquier bloque logístico de entrega.
- Proporcionar accesos rápidos muy claros: "+ Registrar Nuevo Pedido" (`admin.orders.create`) y "Ver Historial Completo" (`admin.orders.index`).

**Non-Goals:**
- No se altera la estructura de la base de datos ni los modelos.
- No se modifican las vistas de creación ni historial de pedidos (`Create.vue`, `Index.vue`).

## Decisions

### 1. Simplificación de la carga de datos en DashboardController
- **Decisión:** En `getBodasStats(int $month, int $year)`, calcular:
  - `registered_today`: `Order::whereDate('registered_at', now()->toDateString())->count()`.
  - `total_orders`: Total en el mes/año.
  - `active_orders`: Total vigentes en el mes/año (`status = 'registrado'`).
  - `cancelled_orders`: Total anulados en el mes/año (`status = 'anulado'`).
- **Decisión:** Eliminar `getBodasTodayDeliveries()` y `getBodasUpcomingDeliveries()`. En su lugar, enriquecer `getBodasRecentOrders()` para que entregue la data completa para la tabla principal.

### 2. Estructura de BodasDashboard.vue
- **Decisión:** Layout centrado en el registro de pedidos:
  - Header con saludo y botones de acción.
  - Fila de 4 tarjetas de métricas cuantitativas de pedidos.
  - Contenedor principal con tabla completa de Pedidos Recientes (Card con tabla responsiva `overflow-x-auto`).

## Risks / Trade-offs

- **[Riesgo: El usuario quiere ver qué pedidos se solicitaron para fechas futuras]** → **Mitigación:** En la tabla de pedidos recientes se muestra claramente la columna "Fecha Solicitada / Entrega" como dato informativo del pedido, pero sin etiquetarlo como logística ni despacho.
