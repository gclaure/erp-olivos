# Company Receipt Format Configuration

Especificación para la configuración del formato de impresión predeterminado (Media Página / Rollo Térmico 80mm) en el panel administrativo.

## Requirements

### Requirement: Selección de formato de impresión en configuración de empresa
El sistema SHALL permitir al administrador configurar el formato de impresión predeterminado (`receipt_type`) de la empresa entre "media" (Media Página / Carta PDF) y "rollo" (Ticket Térmico 80mm).

#### Scenario: Visualización del selector de formato de impresión
- **WHEN** un administrador accede a `/admin/company`
- **THEN** la interfaz muestra una sección de opciones con las alternativas "Media Página / Carta" y "Rollo / Ticket Térmico (80mm)"
- **AND** la opción actual configurada en la empresa aparece seleccionada por defecto

#### Scenario: Guardado y actualización de preferencia de impresión
- **WHEN** el administrador selecciona un formato (`media` o `rollo`) y envía el formulario de configuración
- **THEN** el sistema valida que el valor pertenezca al conjunto permitido (`media`, `rollo`)
- **AND** actualiza el campo `receipt_type` en la base de datos para la empresa
- **AND** los nuevos comprobantes generados toman esta preferencia como formato predeterminado
