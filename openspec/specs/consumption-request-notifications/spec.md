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

## Requirement: Notificar al usuario creador y al personal de Almacén al cancelar

Al cancelar una solicitud de consumo, el Administrador genera una acción crítica que impacta al solicitante y a la logística de almacén. El sistema SHALL enviar la notificación de base de datos `SolicitudConsumoCanceladaNotification` y el evento de WebSocket `NuevaNotificacion` (tipo `consumption_request_cancelled`) a:
1. El usuario que creó la solicitud (`$consumptionRequest->user`), si está activo.
2. Todos los usuarios activos con rol **Almacén** pertenecientes a la misma sucursal del almacén de la solicitud.
Adicionalmente, el sistema MUST emitir el evento en tiempo real `ConsumptionRequestUpdated($consumptionRequest, 'cancelled')` en el canal de sucursal.

### Scenario: Notificación enviada al solicitante y almaceneros tras cancelación
- **WHEN** un Administrador cancela la solicitud de consumo
- **THEN** el usuario solicitante y los usuarios con rol Almacén de la sucursal reciben la notificación en su panel y el sonido/badge en tiempo real

## Requirement: Notificar al usuario creador y al personal de Almacén al modificar cantidad solicitada

Cuando el Administrador modifica la cantidad solicitada de un producto de la solicitud, el sistema SHALL enviar la notificación de base de datos `SolicitudConsumoModificadaNotification` y el evento de WebSocket `NuevaNotificacion` (tipo `consumption_request_modified`) a:
1. El usuario solicitante (`$consumptionRequest->user`), informándole el cambio realizado por el Administrador.
2. Todos los usuarios activos con rol **Almacén** de la sucursal, para que tengan la información actualizada al preparar el pedido.
Adicionalmente, el sistema MUST emitir el evento en tiempo real `ConsumptionRequestUpdated($consumptionRequest, 'item_updated')`.

### Scenario: Notificación enviada tras ajuste de cantidad por el Administrador
- **WHEN** un Administrador modifica la cantidad de un ítem de la solicitud
- **THEN** el creador y el personal de Almacén reciben la notificación con los detalles del producto ajustado y la nueva cantidad

## Requirement: Exclusión estricta del rol Consumidor en notificaciones operativas generales

El sistema SHALL asegurar que los usuarios con rol Consumidor no reciban notificaciones de eventos operativos ajenos a sus solicitudes (tales como recepción de compras de proveedores, discrepancias en transferencias de almacén o ajustes por mermas/inventario).

### Scenario: Consumidor no recibe notificación tras registrar compra
- **WHEN** se recepciona o registra una compra en un almacén de la sucursal del usuario
- **AND** el usuario tiene rol Consumidor
- **THEN** el sistema MUST NOT enviar la notificación de compra recibida a ese usuario

### Scenario: Consumidor no recibe notificación por discrepancia en transferencias
- **WHEN** se confirma la recepción de una transferencia entre almacenes con discrepancia
- **AND** el usuario tiene rol Consumidor
- **THEN** el sistema MUST NOT enviar la notificación de discrepancia a ese usuario

### Scenario: Consumidor no recibe notificación por ajustes o discrepancias de inventario
- **WHEN** se genera un ajuste manual de inventario o discrepancia física
- **AND** el usuario tiene rol Consumidor
- **THEN** el sistema MUST NOT enviar la notificación de inventario a ese usuario

## Requirement: Filtrado de alertas en tiempo real en la vista de solicitudes de consumo

El sistema en frontend SHALL filtrar las alertas sonoras y notificaciones toast ante la creación de solicitudes ajenas cuando el usuario autenticado tiene rol Consumidor.

### Scenario: Consumidor con vista de solicitudes abierta no recibe alerta de solicitud ajena
- **WHEN** otro usuario crea una solicitud de consumo en la misma sucursal
- **AND** el usuario conectado tiene rol Consumidor
- **THEN** la vista de solicitudes MUST actualizar su tabla de datos
- **AND** el sistema MUST NOT emitir sonido ni mostrar toast emergente de nueva solicitud para el Consumidor

### Scenario: Consumidor recibe alerta en tiempo real de eventos de su propia solicitud
- **WHEN** la solicitud creada por el Consumidor es aprobada, despachada, cancelada o modificada
- **THEN** el sistema MUST emitir el sonido de alerta y mostrar el toast correspondiente informándole el estado de su solicitud

## Requirement: Purga automática diaria de notificaciones antiguas con ventana móvil de 90 días

El sistema SHALL ejecutar diariamente de manera automatizada una rutina de limpieza que elimine de la base de datos todas las notificaciones registradas (tanto con estado leído como no leído) cuya antigüedad sea estrictamente superior a 90 días respecto a la fecha actual de ejecución.

### Scenario: Notificaciones mayores a 90 días son eliminadas
- **WHEN** el comando programado de purga se ejecuta automáticamente o de forma manual
- **AND** existen notificaciones en la tabla cuya fecha de creación es anterior a `now()->subDays(90)`
- **THEN** el sistema MUST eliminar permanentemente dichos registros de la base de datos
- **AND** el sistema MUST registrar en los logs de la aplicación la cantidad de registros eliminados

### Scenario: Notificaciones de los últimos 90 días se conservan intactas
- **WHEN** el comando programado de purga se ejecuta
- **AND** existen notificaciones (leídas o no leídas) creadas dentro de la ventana de los últimos 90 días
- **THEN** el sistema MUST conservar intactos todos esos registros para consulta del usuario


