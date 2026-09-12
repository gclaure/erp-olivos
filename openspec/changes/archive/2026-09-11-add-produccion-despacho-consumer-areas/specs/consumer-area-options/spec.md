## MODIFIED Requirements

### Requirement: Áreas operativas disponibles para Consumidores
El sistema SHALL permitir la selección y asignación de áreas operativas a los usuarios que tengan el rol "Consumidor". Las áreas válidas del sistema MUST incluir: `Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción` y `Despacho`.

#### Scenario: Asignar área Producción a un nuevo usuario Consumidor
- **WHEN** un Administrador crea un usuario con rol Consumidor y selecciona "Producción" como área
- **THEN** el sistema guarda el usuario con `area = 'Producción'` y `role = 'Consumidor'`

#### Scenario: Asignar área Despacho a un nuevo usuario Consumidor
- **WHEN** un Administrador crea o edita un usuario con rol Consumidor y selecciona "Despacho" como área
- **THEN** el sistema guarda el usuario con `area = 'Despacho'` y `role = 'Consumidor'`

#### Scenario: Consumidor de Producción o Despacho registra consumo
- **WHEN** un usuario con área `Producción` o `Despacho` envía una solicitud de consumo interno desde el POS
- **THEN** la solicitud se crea con `requested_by` asignado al área correspondiente
- **AND** la validación backend acepta la solicitud sin errores de área inválida
