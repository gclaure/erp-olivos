# POS Product Quantity Stepper

Especificación del stepper de cantidad en las tarjetas del catálogo del POS para pantallas móviles.

## Requirement: Stepper de cantidad visible en móvil

En pantallas con ancho menor a `lg`, el catálogo del POS SHALL mostrar un control de cantidad (`-` / N / `+`) en cada tarjeta de producto con stock disponible, y el agregado al carrito SHALL usar la cantidad mostrada en lugar de 1.

### Scenario: Ajustar cantidad con los controles

- **WHEN** un usuario en una pantalla menor a `lg` pulsa `-` o `+` en una tarjeta de producto con stock disponible
- **THEN** la cantidad mostrada cambia dentro del rango `[1, stock disponible]`
- **AND** al tocar la tarjeta se agrega al carrito la cantidad mostrada
- **AND** el stepper vuelve a mostrar 1 tras el agregado

### Scenario: Cantidad máxima igual al stock disponible

- **WHEN** un usuario intenta superar el stock disponible (físico − reservado) usando `+` o escribiendo en el input
- **THEN** la cantidad se recorta al stock disponible del producto
- **AND** el agregado al carrito respeta ese límite

### Scenario: Producto sin stock

- **WHEN** un producto no tiene stock disponible
- **THEN** la tarjeta permanece deshabilitada (no seleccionable)
- **AND** no se muestra el stepper de cantidad

### Scenario: Pantallas desktop sin cambios

- **WHEN** la pantalla tiene ancho `lg` o mayor
- **THEN** la tarjeta MUST conservar el overlay hover actual que agrega 1 unidad
- **AND** el stepper de cantidad MUST NOT mostrarse

## Requirement: Stepper sincronizado con el carrito

Cuando un producto ya está en el carrito, el stepper de la tarjeta SHALL reflejar la cantidad del carrito y sus controles SHALL actualizar esa cantidad directamente en el carrito.

### Scenario: Producto ya en el carrito muestra su cantidad

- **WHEN** un producto con stock ya está en el carrito en una pantalla menor a `lg`
- **THEN** el input del stepper MUST mostrar la cantidad actual del item en el carrito

### Scenario: Ajustar cantidad de un producto ya en el carrito

- **WHEN** el usuario pulsa `+` o `-` en el stepper de un producto ya en el carrito
- **THEN** la cantidad del item en el carrito MUST incrementarse/decrementarse (mínimo 1) y reflejarse en el sidebar
- **AND** el stepper MUST mostrar el nuevo valor

### Scenario: Editar el input de un producto ya en el carrito

- **WHEN** el usuario escribe un valor en el input del stepper de un producto ya en el carrito
- **THEN** la cantidad del item en el carrito MUST actualizarse a ese valor (mínimo 1)

### Scenario: Tocar la tarjeta de un producto ya en el carrito

- **WHEN** el usuario toca la tarjeta de un producto que ya está en el carrito
- **THEN** la cantidad del carrito MUST NOT incrementarse (la cantidad se controla únicamente desde el stepper)

## Requirement: Badge "En carrito" en móvil

En pantallas menores a `lg`, las tarjetas de productos que ya están en el carrito SHALL mostrar una etiqueta que indique ese estado, sin mostrarla en desktop.

### Scenario: Producto en el carrito muestra la etiqueta

- **WHEN** un producto ya está en el carrito y la pantalla es menor a `lg`
- **THEN** la tarjeta MUST mostrar una etiqueta "En carrito" (con la cantidad actual del item)

### Scenario: Producto no en el carrito sin etiqueta

- **WHEN** un producto no está en el carrito en una pantalla menor a `lg`
- **THEN** la tarjeta MUST NOT mostrar la etiqueta "En carrito"

### Scenario: Desktop no muestra la etiqueta

- **WHEN** la pantalla tiene ancho `lg` o mayor
- **THEN** la etiqueta "En carrito" MUST NOT mostrarse en ninguna tarjeta

## Requirement: addItem del carrito acepta cantidad

El carrito SHALL agregar una cantidad determinada de unidades en una sola operación, mezclando por producto y almacén cuando corresponda, de modo que el subtotal, los descuentos y el total reflejen la cantidad agregada.

### Scenario: Producto ya presente en el carrito

- **WHEN** se agrega un producto con cantidad N y el mismo producto (mismo almacén en modo consumo) ya está en el carrito
- **THEN** la cantidad del item existente MUST incrementarse en N

### Scenario: Producto nuevo en el carrito

- **WHEN** se agrega un producto con cantidad N y no está en el carrito
- **THEN** se crea el item con `quantity = N`
