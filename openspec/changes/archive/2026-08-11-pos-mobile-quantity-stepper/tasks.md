## 1. useCart - addItem con cantidad

- [x] 1.1 Modificar `addItem(product, quantity = 1)` en `resources/js/Composables/POS/useCart.js`: si el item existe, `existing.quantity += quantity`; si no, crear el item con `quantity: quantity` (reemplaza el literal 1 actual en línea 65)

## 2. ProductCard - estado local y stepper móvil

- [x] 2.1 Agregar en `ProductCard.vue` el estado `qty = ref(1)`, el `computed` `maxQty = Math.max(1, Math.floor(availableQty))` y los helpers `increment()`, `decrement()`, `updateInput(e)` con clamp a `[1, maxQty]`
- [x] 2.2 Crear `handleAdd()` que emite `('add', product, qty)` y resetea `qty` a 1; reemplazar el `@click` inline de la tarjeta (línea 35) para que use `handleAdd`
- [x] 2.3 Agregar el stepper `lg:hidden` al final de la tarjeta (`v-if="hasStock"`) con botón `-`, input numérico (`inputmode="numeric"`) y botón `+`
- [x] 2.4 Poner `@click.stop` en el contenedor del stepper y en sus tres controles para no disparar el `add` de la tarjeta
- [x] 2.5 Aplicar variantes oscuras (`dark:secondary-*`) coherentes con el resto de la tarjeta; verificar que sin stock no se muestra el stepper

## 3. ProductCatalog - reenviar la cantidad

- [x] 3.1 En `ProductCatalog.vue` (línea 125) cambiar `@add="$emit('add-to-cart', product)"` a `@add="(product, qty) => $emit('add-to-cart', product, qty)"`

## 4. POS/Index - propagar la cantidad al carrito

- [x] 4.1 En `Index.vue` cambiar `handleAddProduct(product)` a `handleAddProduct(product, qty = 1)` y pasar `qty` a `addItem` en ambas ramas (normal y consumo enriquecido con `warehouse_id`)

## 5. Verificación

- [x] 5.1 Compilar el frontend sin errores (`npm run build` o `npm run dev`)
- [x] 5.2 En viewport `< lg`: `-` / `+` ajustan la cantidad, al tocar la tarjeta se agrega esa cantidad al carrito y el stepper vuelve a 1
- [x] 5.3 En viewport `< lg`: tocar los controles del stepper no agrega de más (sin propagación de clic)
- [x] 5.4 Producto sin stock: tarjeta deshabilitada y sin stepper
- [x] 5.5 En viewport `lg+`: el overlay hover sigue agregando 1 unidad sin cambios
- [x] 5.6 Vista Consumidor: el stepper funciona pero no expone el número de stock disponible
- [x] 5.7 La cantidad agregada se refleja en subtotales, descuentos y total (modo venta) y en la solicitud de consumo (modo consumo), incluido el envío con `item.quantity`

## 6. Sincronización del stepper con el carrito

- [x] 6.1 En `ProductCard.vue` agregar prop `cart` y emit `update-quantity`; calcular `cartItem` (match por `id` + `warehouse_id`) e `isInCart`/`displayQty`
- [x] 6.2 Modificar `increment`/`decrement`/`updateInput` para que, si el producto ya está en el carrito, emitan `update-quantity` (mín 1) en vez de ajustar la cantidad local
- [x] 6.3 Modificar `handleAdd` para que sea no-op cuando el producto ya está en el carrito
- [x] 6.4 En `ProductCatalog.vue` agregar prop `cart` y emit `update-quantity`; reenviar ambos al `ProductCard`
- [x] 6.5 En `Index.vue` pasar `:cart="items"` y `@update-quantity="updateQuantity"` al `ProductCatalog`
- [x] 6.6 Compilar el frontend sin errores (`npm run build`) y verificar que el stepper de un producto ya en el carrito muestra su cantidad y `-`/`+` la reflejan en el sidebar
- [x] 6.7 Agregar en `ProductCard.vue` la etiqueta "En carrito" (solo móvil `lg:hidden`) en la esquina superior izquierda de la imagen, con `v-if="isInCart"` y la cantidad actual (`displayQty`), y compilar sin errores
