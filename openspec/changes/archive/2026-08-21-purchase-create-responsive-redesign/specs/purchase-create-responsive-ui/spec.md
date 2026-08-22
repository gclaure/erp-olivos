## ADDED Requirements

### Requirement: Buscador y Dropdown Adaptativo para Móviles y Tablets
La interfaz de compra (`/admin/purchases/create`) SHALL adaptar el buscador de materias primas y su lista desplegable de resultados para pantallas pequeñas (< 640px) y tablets sin desbordamiento horizontal ni colisión de elementos.

#### Scenario: Visualización del buscador en pantalla móvil (320px - 640px)
- **WHEN** el usuario visualiza el buscador en un dispositivo móvil
- **THEN** la etiqueta del buscador y la indicación del almacén activo se apilan verticalmente sin colisionar
- **AND** el input de búsqueda muestra un placeholder conciso adaptado al ancho de pantalla

#### Scenario: Visualización de resultados de búsqueda en pantalla móvil (< 640px)
- **WHEN** se despliega la lista de productos encontrados en pantalla móvil
- **THEN** cada ítem se muestra en dos filas jerárquicas: fila superior con nombre, código y badge de Materia Prima; fila inferior con un grid de stock en almacén y precio referencial
- **AND** ningún dato de precio o stock se desborda ni se corta lateralmente

### Requirement: Carrito de Compras Dual (Cards en Móvil, Tabla en Desktop)
La interfaz SHALL implementar el patrón híbrido dual para la gestión de productos agregados a la compra: una vista de tarjetas apiladas (`md:hidden`) en móviles y una tabla tabular (`hidden md:block`) en pantallas medianas y grandes.

#### Scenario: Edición de producto en vista móvil (md:hidden)
- **WHEN** un usuario agrega un producto a la compra desde un dispositivo móvil o tablet pequeña (< 768px)
- **THEN** el producto se presenta en una tarjeta operativa individual
- **AND** permite seleccionar el formato de compra, ingresar la cantidad y costo unitario mediante campos táctiles accesibles (min-h 44px)
- **AND** muestra el subtotal de la línea y el botón de eliminar sin necesidad de desplazamiento horizontal

#### Scenario: Visualización en desktop (hidden md:block)
- **WHEN** el usuario accede desde una pantalla de escritorio (≥ 768px)
- **THEN** los productos agregados se muestran en la tabla tradicional con columnas completas y totales
