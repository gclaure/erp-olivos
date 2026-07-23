## Why

Al confirmar recepción de una solicitud de consumo, el Consumidor no tiene manera de registrar una observación libre sobre la recepción (ej: "Paquete arrived damaged", "Cantidad correcta pero sin etiqueta"). El modal de SweetAlert actual solo muestra un botón de confirmación sin campo de texto. Además, la columna `observation` en `consumption_request_details` es compartida entre dispatch y receive — el receive sobreescribe lo que dejó Almacén, perdiendo esa información.

## What Changes

- **Nuevo campo `receive_observation`** en `consumption_request_details` (migración) para preservar la observación del Consumidor sin sobreescribir la de Almacén.
- **Textarea opcional en el modal SweetAlert** de confirmación de recepción, visible para el Consumidor.
- **Enviar `receive_observations`** al backend junto a `received_quantities`.
- **Backend: guardar en `receive_observation`** en vez de sobreescribir `observation`.
- **Mostrar `receive_observation`** en el timeline del Show.vue con patrón visual naranja, distinto de la observación de despacho.

## Capabilities

### New Capabilities

_(none)_

### Modified Capabilities

- `consumer-request-detail-ui`: Agregar requirement de campo de observación en modal de recepción y visualización en timeline.

## Impact

- **Frontend**: `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` — modal SweetAlert con textarea, envío de `receive_observations`, visualización en timeline.
- **Backend**: `app/Services/ConsumptionRequestService.php` — guardar en `receive_observation` en vez de `observation`.
- **Base de datos**: Migración para agregar `receive_observation` nullable en `consumption_request_details`.
- **Model**: `ConsumptionRequestDetail` — agregar `receive_observation` a `$fillable`.
- **Resource**: `ConsumptionRequestDetailResource` — exponer `receive_observation` al frontend.
