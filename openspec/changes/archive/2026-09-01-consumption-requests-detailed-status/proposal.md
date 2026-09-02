## Why

En la lista de solicitudes de consumo (`/admin/consumption-requests`), la columna "Estado" muestra actualmente etiquetas genéricas (ej. `PENDIENTE`) que no le indican al usuario en qué etapa del flujo operativo se encuentra la solicitud (por aprobar, aprobada lista para despacho en almacén, falta de stock para comprar, o despachada pendiente de recepción) ni qué área debe actuar.

## What Changes

- Se enriquece la visualización de la columna **Estado** en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` tanto en la tabla desktop como en las tarjetas móviles:
  - **Pendiente de Aprobación**: Cuando la solicitud fue creada pero no tiene aprobación (`approved_at === null`), indicando `⏳ Por Aprobar · Administración`.
  - **Aprobado / Por Despachar**: Cuando ya cuenta con aprobación (`approved_at !== null` o `status === 'aprobado'`), indicando `📋 Aprobado · Listo en Almacén`.
  - **Falta de Stock**: Cuando la solicitud está pendiente o en preparación pero no hay existencias en bodega (`has_missing_stock === true`), indicando `⚠️ Falta Stock · Requiere Compra`.
  - **Despachado / En Tránsito**: Cuando almacén ya despachó y está esperando recepción del solicitante (`status === 'despachado'`), indicando `🚚 Despachado · Por Recibir`.
  - **Despacho Parcial**: Cuando se entregó una fracción de los ítems (`status === 'despachado_parcial'` o `parcial`), indicando la entrega parcial.
  - **Entregado**: Solicitud completada exitosamente.
  - **Cancelado / Observado**: Con sus respectivas notas y badges de alerta.
- Se actualizan los filtros de búsqueda superior en `Index.vue` para permitir filtrar por estos sub-estados operativos si es necesario.

## Capabilities

### New Capabilities
- `consumption-requests-detailed-status`: Desglose detallado y semántico de etapas de estado en el listado de solicitudes de consumo interno.

### Modified Capabilities
<!-- Sin modificaciones directas en specs de requerimientos funcionales previos -->

## Impact

- Frontend:
  - `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`
- Backend:
  - `app/Http/Resources/ConsumptionRequestResource.php` (asegurar que campos como `approved_at`, `dispatched_at`, `observed_at`, `cancelled_at` y `has_missing_stock` estén disponibles y tipados).
