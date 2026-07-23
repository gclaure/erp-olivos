## Why

Al despachar stock, el usuario de Almacén no tiene manera de registrar una observación libre sobre el despacho (ej: "Producto con empaño", "Se despachó alternativa", "Faltante justificado"). La UI ya tiene un `dispatchObservations` ref y textarea en el formulario, pero `handleDispatch()` nunca envía las observaciones al backend — es un bug existente. Además, la observación guardada en `consumption_request_details.observation` nunca se muestra en el Show.vue del detalle.

## What Changes

- **Enviar observaciones del frontend al backend**: Corregir `handleDispatch()` para incluir `observations` en el payload POST.
- **Observación opcional por ítem**: El usuario de Almacén puede escribir una observación libre por cada producto al despachar. Es opcional (excepto cuando excede stock/pendiente, que ya es requisito existente).
- **Mostrar observación en el detalle**: En Show.vue, mostrar la observación de despacho de cada ítem con el patrón visual "Comentarios de Observación" (estilo badge naranja) tanto en desktop como mobile.
- **Mostrar observación en el historial de la solicitud**: Si el usuario de Almacén dejó observación al despachar, mostrarla también en la sección de timeline/historial de la solicitud.

## Capabilities

### New Capabilities

_(none)_

### Modified Capabilities

- `consumer-request-detail-ui`: Agregar requirement de mostrar observación de despacho del Almacén en el detalle de cada ítem y en el historial de la solicitud.

## Impact

- **Frontend**: `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` — fix `handleDispatch()`, agregar textarea visible por ítem, mostrar observation en detail cards y timeline.
- **Backend**: Ningún cambio necesario — el controller ya valida `observations` y el service ya la procesa. Solo falta el envío desde frontend.
- **Base de datos**: Sin migraciones — `consumption_request_details.observation` ya existe.
