# Proposal

## Why

El rol `Bodas` tiene una responsabilidad puramente comercial y de atención: **registrar pedidos y consultar el listado/historial**. El usuario con este rol no gestiona entregas, retiros, logística ni despachos. Actualmente, el Dashboard de Bodas muestra métricas y bloques de entrega ("Entregas de hoy", "Próximos 7 días", "Prioridad de entrega operativa") que no corresponden a su flujo de trabajo y confunden la operación.

Este cambio refactoriza el Dashboard de Bodas eliminando cualquier referencia a la gestión logística de entregas y reenfocándolo al 100% en la **toma de pedidos, ritmo de registros diarios y consulta rápida del historial**.

## What Changes

- **Eliminación de conceptos de entrega logística:** Se remueven las tarjetas y secciones de "Entregas de hoy", "Próximos 7 días" y "Prioridad de entrega operativa" del Dashboard de Bodas.
- **Nuevos KPIs orientados al registro:**
  - *Registrados Hoy*: Volumen de pedidos tomados en el día en curso.
  - *Total del Mes*: Total de pedidos registrados en el período seleccionado.
  - *Reservas Activas*: Pedidos vigentes/registrados listos para producción.
  - *Anuladas*: Pedidos cancelados con motivo registrado.
- **Tabla principal de Pedidos Recientes:** Se reemplaza el bloque de entregas por una tabla completa y clara de los últimos pedidos registrados con cliente, teléfono, fecha de registro, fecha solicitada de entrega, resumen de productos, estado y accesos para imprimir comanda técnica o ver detalle.
- **Optimización de consultas en backend:** Actualización de `DashboardController` para suprimir queries logísticas y alimentar la nueva estructura de métricas de registro.

## Capabilities

### New Capabilities
<!-- None -->

### Modified Capabilities
- `dashboard/bodas-orders-dashboard`: Reorientación del panel de control de Bodas hacia el registro y listado de pedidos, eliminando secciones de logística de despacho.

## Impact

- **Backend:** `app/Http/Controllers/Admin/DashboardController.php`.
- **Frontend:** `resources/js/Pages/Admin/BodasDashboard.vue`.
- **Base de datos:** Ningún cambio en esquema ni en datos.
