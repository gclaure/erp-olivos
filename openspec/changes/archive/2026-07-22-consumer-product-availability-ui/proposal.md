## Why

Los usuarios con rol Consumidor no deben ver cantidades de stock en el catálogo de `/admin/consumption-requests/create`. Exponer números de inventario es información operativa innecesaria para su flujo y genera confusión; solo necesitan saber si el producto está disponible y cuánto tienen ellos mismos ya reservado en solicitudes pendientes.

## What Changes

- En la lista de productos del POS en modo consumo, para rol **Consumidor**:
  - Ocultar el número de stock disponible.
  - Mostrar estado semántico: **Disponible** (verde) / **No disponible** (rojo), según stock físico menos reservas totales del almacén.
  - Reemplazar el badge `Res: N` (reservas globales) por **`Mis reservados: N`**, solo con la cantidad pendiente del usuario autenticado, y solo si N > 0.
- Exponer desde la API de búsqueda de productos del POS un campo de reservas propias del usuario (`my_reserved_quantity`), sin alterar el cálculo de disponibilidad global.
- Mantener el comportamiento actual (stock numérico + `Res:` global) para Admin y demás roles en la misma pantalla.
- Tooltip del producto para Consumidor sin revelar cantidad de stock.

## Capabilities

### New Capabilities

- `consumer-consumption-catalog-ui`: Reglas de presentación del catálogo de productos en solicitudes de consumo para el rol Consumidor (disponibilidad semántica y “Mis reservados”).

### Modified Capabilities

- (ninguna — no hay specs previas en `openspec/specs/`)

## Impact

- **Backend**: `PosController::searchProducts` (subquery de reservas de consumo del usuario auth); `POSProductResource` (nuevo campo).
- **Frontend**: `ProductCard.vue` (UI condicional); posible flag en `initialConfig` desde `ConsumptionRequestController@create` o detección de rol vía `auth.user.roles`.
- **Sin cambios** en lógica de validación de stock al crear solicitud, carrito, POS de ventas, ni despacho.
- **Roles afectados**: solo `Consumidor` / `consumidor` en modo `operationType === 'consumption'`.
