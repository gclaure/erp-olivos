## 1. Migración y modelo

- [x] 1.1 Crear migración para agregar `receive_observation` (text, nullable) en `consumption_request_details`
- [x] 1.2 Agregar `receive_observation` a `$fillable` en `ConsumptionRequestDetail` model
- [x] 1.3 Exponer `receive_observation` en `ConsumptionRequestDetailResource`

## 2. Backend — Service

- [x] 2.1 Modificar `ConsumptionRequestService::receiveRequest()` para guardar observaciones en `receive_observation` en vez de `observation`
- [x] 2.2 Asegurar que `observation` (dispatch) no se sobreescriba al recepcionar

## 3. Frontend — Modal SweetAlert con textarea

- [x] 3.1 Modificar `handleReceive()` para incluir textarea de observación opcional en el modal SweetAlert usando `html` option
- [x] 3.2 Construir objeto `receiveObsPayload` desde el textarea (filtrar vacíos, trim, max 500 chars)
- [x] 3.3 Enviar `receive_observations` en el POST junto a `received_quantities`

## 4. Frontend — Visualización en timeline

- [x] 4.1 En la sección "Detalles de la Recepción" del timeline, mostrar `receive_observation` por cada detail que tenga contenido
- [x] 4.2 Usar patrón visual naranja para la observación de recepción

## 5. Validación

- [x] 5.1 Ejecutar migración
- [x] 5.2 Validar que el envío funciona (receive_observation llega al backend y se guarda)
- [x] 5.3 Validar que recepcionar sin observación no genera errores
- [x] 5.4 Validar que la observation de despacho se preserva al recepcionar
- [x] 5.5 Ejecutar `openspec validate --changes receive-observation-field`
