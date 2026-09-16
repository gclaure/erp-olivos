# Company Branding Display

Especificación para el control de visualización del nombre de empresa en barra lateral y documentos impresos.

## Requirements

### Requirement: Control de visibilidad del nombre de empresa en menú y PDF
El sistema SHALL mostrar u ocultar el nombre de la empresa en la barra lateral del menú y en el encabezado del comprobante PDF de solicitud de consumo con base en el valor de `company.show_name`.

#### Scenario: Empresa con show_name activo
- **WHEN** `company.show_name` es verdadero (`true`)
- **THEN** en la barra lateral de `AdminLayout` se muestra el nombre de la empresa debajo del logotipo
- **AND** en el PDF de solicitud de consumo se incluye el nombre de la empresa en la cabecera corporativa

#### Scenario: Empresa con show_name desactivado
- **WHEN** `company.show_name` es falso (`false`)
- **THEN** en la barra lateral de `AdminLayout` solo se muestra el logotipo institucional, omitiendo el texto del nombre
- **AND** en el PDF de solicitud de consumo se omite el bloque del nombre corporativo, mostrando únicamente el logotipo y datos de la sucursal
