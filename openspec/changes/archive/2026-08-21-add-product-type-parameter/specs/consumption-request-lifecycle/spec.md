## ADDED Requirements

### Requirement: Disponibilidad continua y bypass de Kardex para Insumos en Solicitudes de Consumo
El sistema SHALL permitir que los productos de tipo `insumo` estén permanentemente disponibles para ser seleccionados en solicitudes de consumo sin importar el balance de stock en almacén, y al momento del despacho MUST NOT descontar stock físico ni generar movimientos de salida en Kardex.

#### Scenario: Selección de Insumo en solicitud de consumo
- **WHEN** un usuario crea o edita una solicitud de consumo y busca un producto de tipo `insumo`
- **THEN** el producto se muestra como disponible con indicador de no inventariable
- **AND** permite seleccionarlo independientemente de que el stock en almacén sea 0

#### Scenario: Despacho de solicitud con Insumo
- **WHEN** el almacén despacha una solicitud aprobada que contiene líneas de productos tipo `insumo`
- **THEN** la cantidad despachada se registra en el detalle de la solicitud (`consumption_request_details`)
- **AND** MUST NOT decrementar el balance en la tabla `stocks`
- **AND** MUST NOT generar movimientos en la tabla `kardex` para dichos ítems
