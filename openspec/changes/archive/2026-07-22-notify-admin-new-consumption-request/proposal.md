## Why

Al registrar una solicitud de consumo en `/admin/consumption-requests/create`, la notificación persistente y el push de campana solo se envían a usuarios con rol Almacén de la sucursal. Los usuarios Admin (y super admin) no reciben la alerta en la campana ni en la base de datos, aunque necesitan enterarse de nuevas solicitudes para operar y supervisar el flujo.

## What Changes

- Ampliar los destinatarios de `NuevaSolicitudConsumoNotification` al crear una solicitud para incluir:
  - Usuarios con rol Almacén (comportamiento actual)
  - Usuarios con rol Admin / Administrador de la misma sucursal del almacén
  - Super admins (`is_super_admin`)
- Excluir explícitamente usuarios con rol Consumidor.
- Mantener el canal `database` + evento `NuevaNotificacion` (socket) por destinatario, como hoy.
- No cambiar el contenido del mensaje ni el tipo `new_consumption_request`.
- Fuera de alcance de este change: notificaciones de recepción/despacho (mismo patrón estrecho, no solicitado ahora).

## Capabilities

### New Capabilities

- `consumption-request-notifications`: Quién recibe notificaciones al crear una solicitud de consumo y con qué criterios de sucursal/rol.

### Modified Capabilities

- (ninguna)

## Impact

- **Backend**: `ConsumptionRequestController@store` (query de destinatarios).
- **Sin cambios** en `NuevaSolicitudConsumoNotification`, frontend de campana, ni broadcast `ConsumptionRequestCreated` (canal de sucursal ya cubre UI de listado).
- **Roles afectados**: Admin y super admin pasan a recibir la notificación; Almacén sin regresión; Consumidor sigue sin recibirla.
