## ADDED Requirements

### Requirement: Persistencia del formato de impresión corporativo
El formulario de configuración de la empresa SHALL enviar y procesar el campo `receipt_type` asegurando su validación y persistencia mediante `CompanyFacade`.

#### Scenario: Actualización de empresa con nuevo receipt_type
- **WHEN** el formulario es enviado con `receipt_type = 'rollo'`
- **THEN** la base de datos almacena el valor `'rollo'` en la empresa
- **AND** la sesión y respuestas de Inertia reflejan el cambio inmediatamente
