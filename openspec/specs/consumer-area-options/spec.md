# Consumer Area Options

Especificación de áreas y departamentos operativos disponibles para usuarios con rol Consumidor y solicitudes de consumo interno.

## Requirement: Áreas operativas disponibles para Consumidores
El sistema SHALL permitir la selección y asignación de áreas operativas a los usuarios que tengan el rol "Consumidor". Las áreas válidas del sistema MUST incluir: `Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción` y `Despacho`.

### Scenario: Asignar área Panadería a un nuevo usuario Consumidor
- **WHEN** un Administrador crea un usuario con rol Consumidor y selecciona "Panadería" como área
- **THEN** el sistema guarda el usuario con `area = 'Panadería'` y `role = 'Consumidor'`

### Scenario: Asignar área Producción a un nuevo usuario Consumidor
- **WHEN** un Administrador crea un usuario con rol Consumidor y selecciona "Producción" como área
- **THEN** el sistema guarda el usuario con `area = 'Producción'` y `role = 'Consumidor'`

### Scenario: Asignar área Despacho a un nuevo usuario Consumidor
- **WHEN** un Administrador crea o edita un usuario con rol Consumidor y selecciona "Despacho" como área
- **THEN** el sistema guarda el usuario con `area = 'Despacho'` y `role = 'Consumidor'`

### Scenario: Consumidor registra consumo con su área operativa
- **WHEN** un usuario con área `Panadería`, `Producción` o `Despacho` envía una solicitud de consumo
- **THEN** la solicitud se crea con `requested_by` asignado al área correspondiente
- **AND** la validación backend acepta la solicitud sin errores de área inválida

## Requirement: Selección dinámica de área operativa para usuarios administradores
El sistema SHALL permitir que los usuarios administradores (o cualquier usuario que no posea un área operativa fija predeterminada en su perfil) seleccionen dinámicamente el área solicitante (`requested_by`) en el panel del carrito al momento de registrar una solicitud de consumo. Las opciones disponibles MUST corresponder a las áreas operativas válidas: `Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción` y `Despacho`.

### Scenario: Administrador registra solicitud seleccionando un área operativa
- **WHEN** un usuario con rol Administrador o sin `area` en su perfil accede a registrar una solicitud de consumo
- **THEN** el panel lateral muestra un selector interactivo con las áreas operativas disponibles
- **AND** al seleccionar un área y enviar la solicitud, el sistema guarda la solicitud con `requested_by` igual al área seleccionada
- **AND** no se muestra el mensaje de alerta "Perfil Incompleto"

### Scenario: Administrador intenta enviar solicitud sin seleccionar área
- **WHEN** un usuario sin área predeterminada intenta enviar la solicitud de consumo sin haber seleccionado un área en el selector
- **THEN** el sistema resalta el campo de selección o solicita seleccionar el área destino antes de procesar el envío
