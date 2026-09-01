## ADDED Requirements

### Requirement: Notificar al usuario creador y al personal de Almacén al cancelar
Al cancelar una solicitud de consumo, el Administrador genera una acción crítica que impacta al solicitante y a la logística de almacén. El sistema SHALL enviar la notificación de base de datos `SolicitudConsumoCanceladaNotification` y el evento de WebSocket `NuevaNotificacion` (tipo `consumption_request_cancelled`) a:
1. El usuario que creó la solicitud (`$consumptionRequest->user`), si está activo.
2. Todos los usuarios activos con rol **Almacén** pertenecientes a la misma sucursal del almacén de la solicitud.
Adicionalmente, el sistema MUST emitir el evento en tiempo real `ConsumptionRequestUpdated($consumptionRequest, 'cancelled')` en el canal de sucursal.

#### Scenario: Notificación enviada al solicitante y almaceneros tras cancelación
- **WHEN** un Administrador cancela la solicitud de consumo
- **THEN** el usuario solicitante y los usuarios con rol Almacén de la sucursal reciben la notificación en su panel y el sonido/badge en tiempo real

### Requirement: Notificar al usuario creador y al personal de Almacén al modificar cantidad solicitada
Cuando el Administrador modifica la cantidad solicitada de un producto de la solicitud, el sistema SHALL enviar la notificación de base de datos `SolicitudConsumoModificadaNotification` y el evento de WebSocket `NuevaNotificacion` (tipo `consumption_request_modified`) a:
1. El usuario solicitante (`$consumptionRequest->user`), informándole el cambio realizado por el Administrador.
2. Todos los usuarios activos con rol **Almacén** de la sucursal, para que tengan la información actualizada al preparar el pedido.
Adicionalmente, el sistema MUST emitir el evento en tiempo real `ConsumptionRequestUpdated($consumptionRequest, 'item_updated')`.

#### Scenario: Notificación enviada tras ajuste de cantidad por el Administrador
- **WHEN** un Administrador modifica la cantidad de un ítem de la solicitud
- **THEN** el creador y el personal de Almacén reciben la notificación con los detalles del producto ajustado y la nueva cantidad
