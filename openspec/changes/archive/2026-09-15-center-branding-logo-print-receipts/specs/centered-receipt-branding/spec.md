## ADDED Requirements

### Requirement: Cabecera tripartita con logotipo centrado en comprobantes PDF
El comprobante imprimible de solicitud de consumo SHALL estructurar su encabezado corporativo en 3 columnas independientes:
1. **Columna Izquierda**: Identidad y datos de la empresa y sucursal emisora.
2. **Columna Central**: Logotipo institucional `logo-light.png` centrado, con escala suficiente para una apreciación óptima del isotipo y texto corporativo.
3. **Columna Derecha**: Metadatos principales del documento (Tipo, Folio/Número, Fecha y Badge de estado).

#### Scenario: Generación y renderizado de cabecera en el recibo de consumo
- **WHEN** se genera el PDF o vista de impresión de una solicitud de consumo
- **THEN** la cabecera muestra los datos de la sucursal alineados a la izquierda
- **AND** el logotipo `logo-light.png` se renderiza centrado horizontalmente en la celda intermedia
- **AND** los metadatos de documento y estado se muestran alineados a la derecha
- **AND** la cabecera mantiene un margen inferior con línea divisoria en color verde olivo de identidad
