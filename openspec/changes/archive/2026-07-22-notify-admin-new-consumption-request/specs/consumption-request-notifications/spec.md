## ADDED Requirements

### Requirement: Notificar a Almacén, Admin y super admin al crear solicitud de consumo

Al crear una o más solicitudes de consumo desde el flujo de registro (`store`), el sistema SHALL enviar la notificación de base de datos `NuevaSolicitudConsumoNotification` (tipo `new_consumption_request`) y el evento de campana `NuevaNotificacion` a cada destinatario elegible activo, sin enviar a usuarios con rol Consumidor.

#### Scenario: Admin de la sucursal del almacén recibe la notificación

- **WHEN** se registra una solicitud de consumo para un almacén de una sucursal
- **AND** existe un usuario activo con rol Admin o Administrador y `branch_id` igual a la sucursal de ese almacén
- **THEN** ese usuario MUST recibir una notificación de base de datos de tipo `new_consumption_request`
- **AND** MUST recibirse el dispatch de campana asociado a su user id

#### Scenario: Super admin recibe la notificación

- **WHEN** se registra una solicitud de consumo
- **AND** existe un usuario activo con `is_super_admin` verdadero
- **THEN** ese usuario MUST recibir la notificación de base de datos y el dispatch de campana correspondientes

#### Scenario: Personal de Almacén de la sucursal sigue recibiendo

- **WHEN** se registra una solicitud de consumo para un almacén de una sucursal
- **AND** existe un usuario activo con rol Almacén (o variantes de nombre usadas en el sistema) y `branch_id` de esa sucursal
- **THEN** ese usuario MUST seguir recibiendo la notificación como antes

#### Scenario: Consumidor no recibe la notificación

- **WHEN** se registra una solicitud de consumo
- **AND** un usuario tiene rol Consumidor
- **THEN** ese usuario MUST NOT recibir `NuevaSolicitudConsumoNotification` ni el dispatch de campana de nueva solicitud

#### Scenario: Usuario con doble rol no se notifica dos veces

- **WHEN** un usuario activo tiene a la vez rol Admin y Almacén en la sucursal del almacén
- **THEN** el sistema MUST enviarle la notificación una sola vez

#### Scenario: Usuario inactivo no recibe

- **WHEN** un Admin o Almacén de la sucursal está inactivo (`is_active` falso)
- **THEN** ese usuario MUST NOT recibir la notificación
