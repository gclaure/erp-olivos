## Context

La vista de detalle de solicitud de consumo (`ConsumptionRequest/Show.vue`) permitía editar las cantidades en el momento del despacho y contenía textareas y botones de dictado de voz por cada fila, generando sobrecarga visual y permitiendo que se despacharan cantidades superiores o diferentes a las aprobadas.

## Goals / Non-Goals

**Goals:**
- Presentar la cantidad a despachar como un valor fijo no editable (`disabled`/badge estilizado) con la cantidad calculada `min(pendiente, stock)`.
- Eliminar los textareas individuales por fila y botones de micrófono en la columna/sección de despacho en `ConsumptionRequest/Show.vue` (tanto en la tabla desktop como en las tarjetas móviles).
- Preservar la observación general en la modal de confirmación de despacho (`Swal.fire`).
- Preservar el dictado por voz para las observaciones de discrepancia en la etapa de recepción (`diff`).

**Non-Goals:**
- No modificar el backend ni la estructura de datos en PostgreSQL.

## Decisions

1. **Cantidad Bloqueada con Alta Legibilidad**:
   - En lugar de un input interactivo editable, mostrar un contenedor estilizado con fondo `bg-zinc-100 dark:bg-secondary-800` y tipografía `font-black`, preservando el binding `dispatchQuantities[item.id]` para el envío del formulario.

2. **Eliminación Limpia de Textareas de Despacho por Ítem**:
   - Limpiar el template de `showDispatchObs` y `dispatchObservations` en las filas de despacho, reduciendo el ruido visual en la tabla y en las tarjetas móviles.

## Risks / Trade-offs

- Ningún riesgo identificado: la cantidad despachada sigue sincronizada con `initQuantities()` y enviada de forma exacta en el payload hacia `admin.consumption-requests.dispatch`.
