# consumption-request-type-filter

Especificación para el filtrado por tipo de producto y disponibilidad de insumos en la creación de solicitudes de consumo.

## Requirements

### Requirement: Filtro de Productos por Tipo (Insumo / Materia Prima)
La interfaz de catálogo de productos SHALL permitir filtrar los resultados por tipo: `Todos`, `Insumos` (`insumo`) y `Materia Prima` (`materia_prima`).

#### Scenario: Filtrado por Insumos
- **WHEN** el usuario selecciona la pestaña o chip "Insumos"
- **THEN** la lista de productos se recarga mostrando exclusivamente productos de tipo `insumo`

#### Scenario: Filtrado por Materia Prima
- **WHEN** el usuario selecciona la pestaña o chip "Materia Prima"
- **THEN** la lista de productos se recarga mostrando exclusivamente productos de tipo `materia_prima`

### Requirement: Disponibilidad Permanente de Insumos para Consumidor
Para usuarios con rol de `Consumidor` (o vista de consumo), los productos clasificados como `type === 'insumo'` SHALL estar siempre activos y disponibles para ser agregados al carrito, independientemente de si el stock registrado en almacén es cero.

#### Scenario: Selección de insumo sin stock físico registrado
- **WHEN** un usuario con rol Consumidor navega por el catálogo de consumo y visualiza un producto de tipo `insumo` con stock 0
- **THEN** la tarjeta del producto permanece activa, muestra el badge "Insumo" y permite añadir la cantidad deseada al carrito de solicitud
