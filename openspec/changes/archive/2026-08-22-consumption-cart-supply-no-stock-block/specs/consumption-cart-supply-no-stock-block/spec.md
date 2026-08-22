# consumption-cart-supply-no-stock-block

Especificación para la validación de stock condicional por tipo de producto y eliminación del badge "Falta Stock" para insumos.

## Requirements

### Requirement: Validación de Stock y Badge "Falta Stock" Exclusivos para Materia Prima
Las advertencias de stock insuficiente en carrito, las validaciones en `SaveConsumptionRequest` y el badge "Falta Stock" en el listado de solicitudes (`ConsumptionRequestResource`) SHALL aplicar únicamente a productos de tipo `materia_prima` (inventariables).

#### Scenario: Solicitud con producto de tipo insumo sin stock en almacén
- **WHEN** un usuario solicita un producto de tipo `insumo` cuyo stock físico en almacén es 0
- **THEN** el carrito permite agregar y procesar la solicitud
- **AND** el backend guarda la solicitud sin errores de validación de stock
- **AND** en el listado de solicitudes (`/admin/consumption-requests`), la solicitud no se marca como `has_missing_stock` ni muestra el badge de alerta `Falta Stock`
