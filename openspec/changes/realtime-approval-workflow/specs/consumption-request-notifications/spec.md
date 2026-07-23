## ADDED Requirements

### Requirement: Notificación de recepción se envía vía WebSocket al Administrador

Al confirmar la recepción de una solicitud de consumo, el sistema SHALL enviar la notificación de recepción vía WebSocket (evento `NuevaNotificacion` en canal `notificaciones.{userId}`) a los Administradores elegibles, además de la notificación de base de datos existente. El tipo de evento de campana MUST ser `consumption_request_received`.

#### Scenario: Admin recibe notificación WebSocket al recepcionar

- **WHEN** el Consumidor confirma la recepción de una solicitud
- **AND** existe un usuario activo con rol Admin/Administrador en la sucursal del almacén de la solicitud
- **THEN** ese usuario MUST recibir la notificación de base de datos
- **AND** MUST recibirse el dispatch de campana `NuevaNotificacion` con tipo `consumption_request_received`

#### Scenario: Super admin recibe notificación WebSocket al recepcionar

- **WHEN** el Consumidor confirma la recepción de una solicitud
- **AND** existe un super admin activo
- **THEN** ese super admin MUST recibir la notificación de base de datos y el dispatch de campana

#### Scenario: Bell de notificaciones se actualiza en tiempo real

- **WHEN** un Admin tiene abierto el sistema y el Consumidor confirma una recepción
- **THEN** el badge de la campana de notificaciones MUST actualizarse inmediatamente mostrando el conteo de no leídas incrementado
- **AND** la notificación MUST aparecer en el dropdown de notificaciones sin recarga de página
