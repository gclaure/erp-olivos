## Why

En pantallas móviles (< `lg`) el catálogo del POS solo permite agregar 1 unidad por toque en la tarjeta de producto. Para poner en el carrito lo que se necesita, el usuario debe tocar repetidamente la tarjeta o cambiar a la pestaña de carrito y ajustar la cantidad ahí. No existe forma de elegir cuánto agregar directamente desde el catálogo, lo que hace la operación lenta y propensa a errores.

## What Changes

- Agregar un stepper de cantidad (`-` / N / `+`) en cada tarjeta de producto con stock disponible, visible **solo** en pantallas móviles (`< lg`)
- Los controles `-` y `+` ajustan la cantidad mostrada; al tocar la tarjeta se agrega **esa** cantidad al carrito y el número vuelve a 1
- La cantidad máxima del stepper es el stock disponible del producto (físico − reservado), calculado con el `availableQty` existente
- El flujo desktop (`lg+`) no cambia: el overlay hover con botón `+ Agregar` sigue agregando 1 unidad
- `addItem` del carrito acepta una cantidad para agregar de una sola vez (en vez de invocarse N veces)
- En la vista del rol Consumidor el stepper aplica su límite de stock en silencio, sin exponer el número de stock disponible

## Capabilities

### New Capabilities

- `pos-product-quantity-stepper`: Stepper de cantidad en las tarjetas del catálogo POS para pantallas móviles, con límite por stock disponible y agregado de la cantidad seleccionada al carrito

### Modified Capabilities

- `consumer-consumption-catalog-ui`: El rol Consumidor puede seleccionar la cantidad a solicitar mediante el stepper sin que se exponga el número de stock disponible como límite visible

## Impact

- **Frontend**:
  - `resources/js/Pages/Admin/POS/Partials/ProductCard.vue` — stepper móvil, estado local de cantidad y emisión de `add(product, qty)`
  - `resources/js/Pages/Admin/POS/Partials/ProductCatalog.vue` — reenviar la cantidad en el evento `add-to-cart`
  - `resources/js/Pages/Admin/POS/Index.vue` — `handleAddProduct` acepta y propaga la cantidad
  - `resources/js/Composables/POS/useCart.js` — `addItem` acepta `quantity`
- **Backend**: Sin cambios
- **Dependencias**: Ninguna nueva
