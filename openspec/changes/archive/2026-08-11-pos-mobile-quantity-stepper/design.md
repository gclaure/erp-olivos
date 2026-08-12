## Context

El catálogo del POS (`ProductCatalog.vue`) renderiza una grilla de `ProductCard.vue`. Cada tarjeta:

- Emite `add` con el producto en su `@click` (`ProductCard.vue:35`), lo que agrega **1 unidad** al carrito por toque.
- Muestra un overlay hover con botón "+ Agregar" solo en desktop (`hidden lg:flex`, línea 57).
- Calcula `availableQty = físico − reservado` y bloquea la tarjeta (`opacity-60 cursor-not-allowed`) cuando no hay stock.

El carrito (`Composables/POS/useCart.js`) hace merge por `id` (+ `warehouse_id` en modo consumo) e incrementa la cantidad de 1 en 1 en `addItem` (línea 65).

Cadena del evento: `ProductCard @add` → `ProductCatalog $emit('add-to-cart')` → `POS/Index.vue handleAddProduct` → `addItem`.

En móvil (`< lg`) no hay forma de elegir cantidad: cada toque agrega 1.

## Goals / Non-Goals

**Goals:**
- Stepper de cantidad (`-` / N / `+`) en cada tarjeta con stock, visible solo en pantallas móviles (`< lg`)
- Al tocar la tarjeta se agrega al carrito la cantidad mostrada y el número vuelve a 1
- Cantidad máxima = stock disponible (`floor(availableQty)`)
- El flujo desktop (`lg+`) permanece idéntico (overlay hover agrega 1)
- `addItem` acepta una cantidad para agregar de una sola vez
- En la vista Consumidor, el límite aplica en silencio sin exponer el número de stock

**Non-Goals:**
- No mostrar el stepper en desktop
- No cambios de backend ni de `CartSidebar` / `updateQuantity` (se reutiliza el mismo handler)
- No cambiar el comportamiento de la tarjeta en escritorio ni el merge por `warehouse_id`

## Decisions

### 1. Cantidad local por tarjeta: "selector + tocar el card agrega"

**Decisión**: `ProductCard` mantiene `qty = ref(1)` para el estado de "pendiente de agregar". Los controles `-` / `+` y el input ajustan `qty`; al tocar la tarjeta, `handleAdd()` emite `('add', product, qty)` y resetea `qty` a 1. El card completo sigue siendo el gatillo de agregado (mismo patrón visual que hoy).

**Alternativa considerada**: Que el botón `+` del stepper agregue directamente al carrito. **Rechazada** porque el usuario eligió explícitamente el patrón "selector + tocar card agrega", y mantener el card como gatillo conserva el hábito táctil existente.

### 1b. Sincronización con el carrito cuando el producto ya existe

**Decisión**: El stepper recibe el carrito por props (`cart`) y calcula `cartItem` (match por `id`, más `warehouse_id` cuando existe). Si el producto ya está en el carrito:

- El input muestra `displayQty = cartItem.quantity` (no la cantidad local).
- `-` / `+` y el input emiten `update-quantity` con `Math.max(1, qty ∓ 1)` / el valor escrito, actualizando el carrito directamente (mismo handler `updateQuantity` de `useCart` que usa el `CartSidebar`).
- Tocar la tarjeta es no-op (`handleAdd` retorna si `isInCart`), evitando agregados accidentales sobre la cantidad ya controlada.

Si el producto NO está en el carrito, se conserva la decisión 1 (cantidad local + tocar card agrega). Tras agregar, el stepper se sincroniza automáticamente porque `displayQty` reacciona a `cartItem`.

**Razón**: El usuario pidió que el valor del stepper se refleje en el carrito al presionar `-` / `+` cuando el producto ya existe. Se reutiliza `updateQuantity` y el flujo reactivo del mismo array `items`, por lo que el sidebar se actualiza al instante.

### 2. `addItem(product, quantity = 1)` en useCart

**Decisión**: `addItem` acepta una cantidad: si el item existe, `existing.quantity += quantity`; si no, se crea con `quantity: quantity`. Se conserva el merge por `id` + `warehouse_id`.

**Razón**: Agregar N unidades invocando `addItem` N veces obligaría a propagar N eventos y complicaría el merge; con el parámetro es una sola operación y el resto del carrito (subtotales, descuentos, envío) funciona igual porque ya consume `item.quantity`.

### 3. Stepper solo móvil y aislado de la propagación de clics

**Decisión**: El stepper se renderiza con `class="lg:hidden"` y solo cuando `hasStock` es true. El contenedor y los tres controles (`-`, input, `+`) llevan `@click.stop` para que sus toques no disparen el `add` del card.

**Razón**: Sin `@click.stop`, tocar `+` agregaría doble (el del control y el del `@click` del card). En desktop el contenedor no existe por `lg:hidden`, por lo que el overlay hover actual queda intacto.

### 4. Límite por stock disponible, silencioso para el rol Consumidor

**Decisión**: `maxQty = Math.max(1, Math.floor(availableQty))`. `-` / `+` y el input se recortan al rango `[1, maxQty]`. La tarjeta no muestra el máximo en la vista Consumidor (consistente con la regla existente de ocultar stock numérico); el límite solo se aplica al recortar.

**Razón**: Reutiliza el `availableQty` ya calculado y mantiene invariante la regla de no exponer stock numérico al Consumidor.

### 5. Entrada editable con sanitización

**Decisión**: El input usa `inputmode="numeric"` para teclado numérico en móvil y se parsea con `parseInt` en cada `@input`, recortando valores no numéricos, `0` o negativos a `1`, y valores superiores a `maxQty`.

**Razón**: Evita cantidades inválidas (vacíos, `NaN`, decimales) que romperían subtotales o validaciones al confirmar.

## Risks / Trade-offs

- **Riesgo**: Tocar los controles del stepper dispara el `add` del card por propagación → **Mitigación**: `@click.stop` en el contenedor y en los tres controles.
- **Riesgo**: Input libre con valores inválidos o vacíos → **Mitigación**: parse + clamp a `[1, maxQty]` en cada entrada.
- **Riesgo**: `qty` quede por encima del stock si el stock cambia entre renders → **Mitigación**: cada operación (`increment`/`decrement`/`updateInput`) recorta a `maxQty`; el agregado usa `handleAdd` que envía el valor ya recortado.
- **Trade-off**: La cantidad local no refleja lo ya agregado al carrito (si el producto ya tiene 5, el stepper muestra 1). Se acepta por simplicidad; el `CartSidebar` sigue permitiendo ajustar cantidades ya en el carrito.

## Migration Plan

- Cambio puramente frontend y aditivo: se puede desplegar sin migración de datos ni cambios de backend.
- **Rollback**: Revertir los 4 archivos frontend; el comportamiento móvil vuelve a "1 unidad por toque" sin afectar ventas ya creadas.
