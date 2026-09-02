## Why

En la vista de catálogo y punto de venta/consumo (`/admin/consumption-requests/create` y POS), los badges flotantes superiores de las tarjetas de producto colisionan entre sí en tarjetas estrechas (ej. en grillas de 3 o 4 columnas): el botón `[ ℹ️ DETALLES ]` a la izquierda y el badge `[ DISPONIBLE ]` a la derecha se superponen directamente, degradando la experiencia visual del usuario.

## What Changes

- Reorganizar la estructura de las tarjetas de producto en `resources/js/Pages/Admin/POS/Partials/ProductCard.vue`.
- Mantener la zona de imagen limpia con únicamente el badge de stock/disponibilidad en la esquina superior derecha (`top-2 right-2`).
- Ubicar el botón de ficha técnica **`[ ℹ️ Detalles ]`** en la barra de información del producto (junto al código/código de barras), garantizando que nunca choque con el badge de stock y mantenga un área táctil y clicable cómoda.
- Garantizar soporte responsivo y alta fidelidad en temas claro y oscuro.

## Capabilities

### New Capabilities
- `pos-product-card-layout-redesign`: Rediseño estructural y responsivo de la tarjeta de producto en catálogo y POS para evitar colisiones entre insignias de stock y botones de detalles.

### Modified Capabilities

## Impact

- `resources/js/Pages/Admin/POS/Partials/ProductCard.vue`
