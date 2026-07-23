## 1. Backend — regla de despacho

- [x] 1.1 En `ConsumptionRequestDispatchService`, restringir estados despachables a `aprobado` y `despachado_parcial` (quitar `pendiente`)
- [x] 1.2 Ajustar mensaje de excepción y cualquier validación espejo en `ConsumptionRequestController::dispatchRequest` / `canUserDispatch` del frontend

## 2. Backend — notificaciones

- [x] 2.1 Crear `SolicitudConsumoAprobadaNotification` y enviarla al creador activo desde `approve` (+ `NuevaNotificacion` tipo `consumption_request_approved`)
- [x] 2.2 Ajustar `dispatchRequest` para notificar al creador activo (reutilizar/ajustar `SolicitudConsumoDespachadaNotification`)
- [x] 2.3 Ajustar `receive` para notificar Admin/Administrador de la sucursal + super admin (dedupe), y dejar de notificar a Almacén

## 3. Frontend — Show.vue

- [x] 3.1 Alinear `canUserDispatch` / botones de despacho con estados `aprobado` | `despachado_parcial` únicamente
- [x] 3.2 Verificar que Aprobar (Admin) y Confirmar Recepción (Consumidor) sigan visibles solo en sus estados correctos
- [x] 3.3 Asegurar que el panel de acciones no ofrezca despacho al Consumidor ni desde `pendiente`

## 4. Verificación

- [x] 4.1 Revisar que `store` / create no hayan sido modificados
- [x] 4.2 Validar change con `openspec validate`
