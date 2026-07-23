## Why

El modal de confirmación de despacho ("¿Confirmar Despacho?") actualmente solo muestra un mensaje de texto. A diferencia del modal de recepción que ya incluye un textarea opcional para observaciones, el despacho no ofrece esta oportunidad. El usuario Almacén necesita poder registrar una nota general antes de confirmar el despacho (ej: "entregado en bodega 2", "faltante justificado por daño").

## What Changes

- Agregar textarea opcional al modal SweetAlert de confirmación de despacho, con contador de caracteres (0/500)
- Enviar la observación del modal como `dispatch_observation` en el POST al backend
- La observación del modal es general (aplica a todo el despacho, no por ítem)
- Las observaciones por ítem existentes (`observations`) se mantienen sin cambios

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `consumer-request-detail-ui`: Agregar requirement para observación opcional en modal de despacho

## Impact

- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`: Modal `handleDispatch` (línea ~385) — cambiar `text` por `html` con textarea, agregar `didOpen`, leer valor en confirmación
- Spec `consumer-request-detail-ui`: Nuevo requirement
