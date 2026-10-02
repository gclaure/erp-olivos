# Spec Delta

## ADDED Requirements

### Requirement: Exclusión estricta del rol Consumidor en notificaciones operativas generales
El sistema SHALL asegurar que los usuarios con rol Consumidor no reciban notificaciones de eventos operativos ajenos a sus solicitudes (tales como recepción de compras de proveedores, discrepancias en transferencias de almacén o ajustes por mermas/inventario).

#### Scenario: Consumidor no recibe notificación tras registrar compra
- **WHEN** se recepciona o registra una compra en un almacén de la sucursal del usuario
- **AND** el usuario tiene rol Consumidor
- **THEN** el sistema MUST NOT enviar la notificación de compra recibida a ese usuario

#### Scenario: Consumidor no recibe notificación por discrepancia en transferencias
- **WHEN** se confirma la recepción de una transferencia entre almacenes con discrepancia
- **AND** el usuario tiene rol Consumidor
- **THEN** el sistema MUST NOT enviar la notificación de discrepancia a ese usuario

#### Scenario: Consumidor no recibe notificación por ajustes o discrepancias de inventario
- **WHEN** se genera un ajuste manual de inventario o discrepancia física
- **AND** el usuario tiene rol Consumidor
- **THEN** el sistema MUST NOT enviar la notificación de inventario a ese usuario

### Requirement: Filtrado de alertas en tiempo real en la vista de solicitudes de consumo
El sistema en frontend SHALL filtrar las alertas sonoras y notificaciones toast ante la creación de solicitudes ajenas cuando el usuario autenticado tiene rol Consumidor.

#### Scenario: Consumidor con vista de solicitudes abierta no recibe alerta de solicitud ajena
- **WHEN** otro usuario crea una solicitud de consumo en la misma sucursal
- **AND** el usuario conectado tiene rol Consumidor
- **THEN** la vista de solicitudes MUST actualizar su tabla de datos
- **AND** el sistema MUST NOT emitir sonido ni mostrar toast emergente de nueva solicitud para el Consumidor

#### Scenario: Consumidor recibe alerta en tiempo real de eventos de su propia solicitud
- **WHEN** la solicitud creada por el Consumidor es aprobada, despachada, cancelada o modificada
- **THEN** el sistema MUST emitir el sonido de alerta y mostrar el toast correspondiente informándole el estado de su solicitud
