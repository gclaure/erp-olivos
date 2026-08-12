# Consumption Request Notifications

Especificación de quién recibe notificaciones al crear y operar solicitudes de consumo.

## Requirement: Notificar a Almacén, Admin y super admin al crear solicitud de consumo

Al crear una o más solicitudes de consumo desde el flujo de registro (`store`), el sistema SHALL enviar la notificación de base de datos `NuevaSolicitudConsumoNotification` (tipo `new_consumption_request`) y el evento de campana `NuevaNotificacion` a cada destinatario elegible activo, sin enviar a usuarios con rol Consumidor.

### Scenario: Admin de la sucursal del almacén recibe la notificación

- **WHEN** se registra una solicitud de consumo para un almacén de una sucursal
- **AND** existe un usuario activo con rol Admin o Administrador y `branch_id` igual a la sucursal de ese almacén
- **THEN** ese usuario MUST recibir una notificación de base de datos de tipo `new_consumption_request`
- **AND** MUST recibirse el dispatch de campana asociado a su user id

### Scenario: Super admin recibe la notificación

- **WHEN** se registra una solicitud de consumo
- **AND** existe un usuario activo con `is_super_admin` verdadero
- **THEN** ese usuario MUST recibir la notificación de base de datos y el dispatch de campana correspondientes

### Scenario: Personal de Almacén de la sucursal sigue recibiendo

- **WHEN** se registra una solicitud de consumo para un almacén de una sucursal
- **AND** existe un usuario activo con rol Almacén (o variantes de nombre usadas en el sistema) y `branch_id` de esa sucursal
- **THEN** ese usuario MUST seguir recibiendo la notificación como antes

### Scenario: Consumidor no recibe la notificación

- **WHEN** se registra una solicitud de consumo
- **AND** un usuario tiene rol Consumidor
- **THEN** ese usuario MUST NOT recibir `NuevaSolicitudConsumoNotification` ni el dispatch de campana de nueva solicitud

### Scenario: Usuario con doble rol no se notifica dos veces

- **WHEN** un usuario activo tiene a la vez rol Admin y Almacén en la sucursal del almacén
- **THEN** el sistema MUST enviarle la notificación una sola vez

### Scenario: Usuario inactivo no recibe

- **WHEN** un Admin o Almacén de la sucursal está inactivo (`is_active` falso)
- **THEN** ese usuario MUST NOT recibir la notificación

## Requirement: Notificar al Consumidor creador al aprobar

Al aprobar una solicitud de consumo, el sistema SHALL enviar notificación de base de datos y evento de campana `NuevaNotificacion` al usuario creador (`user_id`) si está activo, informando que la solicitud fue aprobada. El tipo de evento de campana MUST ser `consumption_request_approved`.

### Scenario: Creador activo recibe notificación de aprobación

- **WHEN** un Administrador aprueba una solicitud
- **AND** el usuario creador está activo
- **THEN** el creador MUST recibir una notificación de base de datos de aprobación
- **AND** MUST recibirse el dispatch de campana asociado a su user id con tipo `consumption_request_approved`

### Scenario: Creador inactivo no recibe

- **WHEN** un Administrador aprueba una solicitud
- **AND** el usuario creador está inactivo
- **THEN** el sistema MUST NOT fallar la aprobación
- **AND** MUST NOT enviar notificación al creador inactivo

## Requirement: Notificar al Consumidor creador al despachar

Al despachar una solicitud de consumo, el sistema SHALL notificar al usuario creador activo que la solicitud fue despachada y puede ser recepcionada, vía notificación de base de datos y campana (`consumption_request_dispatched`).

### Scenario: Creador recibe notificación de despacho

- **WHEN** Almacén completa un despacho (total o parcial)
- **AND** el creador está activo
- **THEN** el creador MUST recibir la notificación de despacho y el dispatch de campana

## Requirement: Notificar al Administrador al recepcionar

Al confirmar la recepción de una solicitud de consumo, el sistema SHALL notificar a los Administradores elegibles (usuarios activos con rol Admin/Administrador de la sucursal del almacén de la solicitud, y super admins), con deduplicación, vía notificación de base de datos y vía WebSocket (evento `NuevaNotificacion` en canal `notificaciones.{userId}`). El sistema MUST NOT enviar esta notificación a usuarios con solo rol Almacén ni a usuarios con solo rol Consumidor. Tipo de campana: `consumption_request_received`.

### Scenario: Admin de sucursal recibe notificación de recepción

- **WHEN** el Consumidor confirma la recepción
- **AND** existe un Admin/Administrador activo de la sucursal del almacén
- **THEN** ese Admin MUST recibir la notificación de recepción y el dispatch de campana

### Scenario: Almacén no recibe notificación de recepción

- **WHEN** el Consumidor confirma la recepción
- **AND** un usuario tiene solo rol Almacén en la sucursal
- **THEN** ese usuario MUST NOT recibir la notificación de recepción

### Scenario: Super admin recibe notificación de recepción

- **WHEN** el Consumidor confirma la recepción
- **AND** existe un super admin activo
- **THEN** ese super admin MUST recibir la notificación de recepción

### Scenario: Bell de notificaciones se actualiza en tiempo real

- **WHEN** un Admin tiene abierto el sistema y el Consumidor confirma una recepción
- **THEN** el badge de la campana de notificaciones MUST actualizarse inmediatamente mostrando el conteo de no leídas incrementado
- **AND** la notificación MUST aparecer en el dropdown de notificaciones sin recarga de página
