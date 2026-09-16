## ADDED Requirements

### Requirement: Estandarización de logotipos estáticos por contexto
El sistema SHALL utilizar de forma fija los logotipos canónicos según el contexto de visualización:
1. Para superficies oscuras (como la barra lateral del panel administrativo), el sistema SHALL renderizar `/img/logo-dark.png`.
2. Para superficies claras, documentos impresos y comprobantes PDF (como recibos de solicitudes de consumo), el sistema SHALL renderizar `public_path('img/logo-light.png')` (o `/img/logo-light.png` en vistas web de fondo blanco).

#### Scenario: Visualización en barra lateral administrativa
- **WHEN** un usuario autenticado navega por el panel de administración
- **THEN** la barra lateral muestra `/img/logo-dark.png`
- **AND** muestra el nombre comercial de la empresa solo si `company.show_name` está habilitado

#### Scenario: Generación de comprobante PDF / Recibo de consumo
- **WHEN** se genera el PDF de una solicitud de consumo o reporte imprimible
- **THEN** el encabezado del documento utiliza la ruta física `public_path('img/logo-light.png')`
- **AND** renderiza el nombre de la empresa condicionado a `company.show_name`

#### Scenario: Previsualización en la configuración de la empresa
- **WHEN** el administrador accede a `/admin/company`
- **THEN** la tarjeta de previsualización muestra `/img/logo-light.png` como referencia de marca sobre fondo claro
