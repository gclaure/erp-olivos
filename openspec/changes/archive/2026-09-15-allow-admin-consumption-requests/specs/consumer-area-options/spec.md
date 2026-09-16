## ADDED Requirements

### Requirement: Selección dinámica de área operativa para usuarios administradores
El sistema SHALL permitir que los usuarios administradores (o cualquier usuario que no posea un área operativa fija predeterminada en su perfil) seleccionen dinámicamente el área solicitante (`requested_by`) en el panel del carrito al momento de registrar una solicitud de consumo. Las opciones disponibles MUST corresponder a las áreas operativas válidas: `Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción` y `Despacho`.

#### Scenario: Administrador registra solicitud seleccionando un área operativa
- **WHEN** un usuario con rol Administrador o sin `area` en su perfil accede a registrar una solicitud de consumo
- **THEN** el panel lateral muestra un selector interactivo con las áreas operativas disponibles
- **AND** al seleccionar un área y enviar la solicitud, el sistema guarda la solicitud con `requested_by` igual al área seleccionada
- **AND** no se muestra el mensaje de alerta "Perfil Incompleto"

#### Scenario: Administrador intenta enviar solicitud sin seleccionar área
- **WHEN** un usuario sin área predeterminada intenta enviar la solicitud de consumo sin haber seleccionado un área en el selector
- **THEN** el sistema resalta el campo de selección o solicita seleccionar el área destino antes de procesar el envío
