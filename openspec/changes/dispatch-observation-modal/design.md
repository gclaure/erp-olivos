## Context

El modal de confirmación de despacho en `Show.vue:385` usa `text:` (mensaje simple). El modal de recepción ya implementa el patrón deseado con `html:`, textarea, contador y `didOpen`. Se reutiliza exactamente el mismo patrón.

El despacho ya tiene observaciones por ítem (`dispatchObservations`). La observación del modal es una nota general adicional, no las reemplaza.

## Goals / Non-Goals

**Goals:**
- Agregar textarea opcional al modal de despacho con contador 0/500
- Enviar `dispatch_observation` como campo separado en el POST
- Reutilizar el patrón exacto del modal de recepción

**Non-Goals:**
- Modificar las observaciones por ítem existentes
- Cambiar el backend (el campo `dispatch_observation` ya existe en el modelo)

## Decisions

**Decisión 1: Cambiar `text:` por `html:` con textarea**

Mismo patrón que el modal de recepción:
- `html:` con textarea + contador
- `didOpen:` para actualizar contador en tiempo real
- Leer valor con `document.getElementById('swal-dispatch-obs')` en `result.isConfirmed`

**Decisión 2: Campo `dispatch_observation` separado de `observations`**

- `observations`: objeto `{ detail_id: "obs" }` — observaciones por ítem
- `dispatch_observation`: string — observación general del modal
- Ambos se envían en el POST, se almacenan en columnas separadas

## Risks / Trade-offs

**Riesgo**: Confusión entre observación del modal y observaciones por ítem.
**Mitigación**: Labels claros — "Observación general (opcional)" en el modal vs "Observación (opcional)" por ítem.
