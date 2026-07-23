## ADDED Requirements

### Requirement: Notificar al Consumidor creador al aprobar

Al aprobar una solicitud de consumo, el sistema SHALL enviar notificación de base de datos y evento de campana `NuevaNotificacion` al usuario creador (`user_id`) si está activo, informando que la solicitud fue aprobada. El tipo de evento de campana MUST ser `consumption_request_approved`.

#### Scenario: Creador activo recibe notificación de aprobación
- **WHEN** un Administrador aprueba una solicitud
- **AND** el usuario creador está activo
- **THEN** el creador MUST recibir una notificación de base de datos de aprobación
- **AND** MUST recibirse el dispatch de campana asociado a su user id con tipo `consumption_request_approved`

#### Scenario: Creador inactivo no recibe
- **WHEN** un Administrador aprueba una solicitud
- **AND** el usuario creador está inactivo
- **THEN** el sistema MUST NOT fallar la aprobación
- **AND** MUST NOT enviar notificación al creador inactivo

### Requirement: Notificar al Consumidor creador al despachar

Al despachar una solicitud de consumo, el sistema SHALL notificar al usuario creador activo que la solicitud fue despachada y puede ser recepcionada, vía notificación de base de datos y campana (`consumption_request_dispatched`).

#### Scenario: Creador recibe notificación de despacho
- **WHEN** Almacén completa un despacho (total o parcial)
- **AND** el creador está activo
- **THEN** el creador MUST recibir la notificación de despacho y el dispatch de campana

### Requirement: Notificar al Administrador al recepcionar

Al confirmar la recepción de una solicitud de consumo, el sistema SHALL notificar a los Administradores elegibles (usuarios activos con rol Admin/Administrador de la sucursal del almacén de la solicitud, y super admins), con deduplicación. El sistema MUST NOT enviar esta notificación a usuarios con solo rol Almacén ni a usuarios con solo rol Consumidor. Tipo de campana: `consumption_request_received`.

#### Scenario: Admin de sucursal recibe notificación de recepción
- **WHEN** el Consumidor confirma la recepción
- **AND** existe un Admin/Administrador activo de la sucursal del almacén
- **THEN** ese Admin MUST recibir la notificación de recepción y el dispatch de campana

#### Scenario: Almacén no recibe notificación de recepción
- **WHEN** el Consumidor confirma la recepción
- **AND** un usuario tiene solo rol Almacén en la sucursal
- **THEN** ese usuario MUST NOT recibir la notificación de recepción

#### Scenario: Super admin recibe notificación de recepción
- **WHEN** el Consumidor confirma la recepción
- **AND** existe un super admin activo
- **THEN** ese super admin MUST recibir la notificación de recepción
