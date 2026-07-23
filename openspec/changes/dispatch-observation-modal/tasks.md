## 1. Migración

- [x] 1.1 Crear migración `add_dispatch_observation_to_consumption_requests_table` para agregar columna `dispatch_observation` (text, nullable) a `consumption_requests`
- [x] 1.2 Ejecutar migración

## 2. Backend

- [x] 2.1 Agregar `dispatch_observation` al `$fillable` de `ConsumptionRequest.php`
- [x] 2.2 Validar `dispatch_observation` como `nullable|string|max:500` en `dispatchRequest()` del controlador
- [x] 2.3 Guardar `dispatch_observation` en el modelo dentro del servicio `ConsumptionRequestService::dispatchRequest()`

## 3. Frontend

- [x] 3.1 Cambiar el modal de confirmación de despacho en `Show.vue:385` de `text:` a `html:` con textarea opcional, contador 0/500 y `didOpen`
- [x] 3.2 En `result.isConfirmed`, leer el valor del textarea (`swal-dispatch-obs`), truncar a 500, enviar como `dispatch_observation` en el POST

## 4. Specs

- [x] 4.1 Sincronizar delta spec de `consumer-request-detail-ui` con el main spec
