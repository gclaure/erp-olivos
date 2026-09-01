# Consumer Area Options

Especificación de áreas y departamentos operativos disponibles para usuarios con rol Consumidor y solicitudes de consumo interno.

## Requirement: Áreas operativas disponibles para Consumidores
El sistema SHALL permitir la selección y asignación de áreas operativas a los usuarios que tengan el rol "Consumidor". Las áreas válidas del sistema MUST incluir: `Cocina`, `Pastelería`, `Panadería` y `Eventos`.

### Scenario: Asignar área Panadería a un nuevo usuario Consumidor
- **WHEN** un Administrador crea un usuario con rol Consumidor y selecciona "Panadería" como área
- **THEN** el sistema guarda el usuario con `area = 'Panadería'` y `role = 'Consumidor'`

### Scenario: Consumidor de Panadería registra consumo
- **WHEN** un usuario con área `Panadería` envía una solicitud de consumo
- **THEN** la solicitud se crea con `requested_by = 'Panadería'`
- **AND** la validación backend acepta la solicitud sin errores de área inválida
