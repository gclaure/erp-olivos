## Context

La tarjeta de producto `ProductCard.vue` muestra imagen, badges y detalles de inventario o precio. En el diseño previo, el botón flotante `[ ℹ️ DETALLES ]` en la esquina superior izquierda chocaba directamente con el badge de disponibilidad `[ DISPONIBLE ]` en la esquina superior derecha cuando la tarjeta se estrechaba en pantallas medianas o vistas de 3-4 columnas.

## Goals / Non-Goals

**Goals:**
- Mover el botón de ficha técnica `[ ℹ️ Detalles ]` a la fila de información del producto (al lado del código).
- Dejar la cabecera de la imagen limpia, con el badge de disponibilidad/stock únicamente en `top-2 right-2`.
- Mantener la funcionalidad de `@click.stop="emit('show-detail', product)"` y el badge móvil de cantidad en carrito.

**Non-Goals:**
- No alterar la lógica de cálculo de stock, precios o el modal de detalle `ProductDetailModal.vue`.

## Decisions

### 1. Zona de Imagen limpia
En la imagen solo queda:
- Badge de stock/disponibilidad en `absolute top-2 right-2 sm:top-3 sm:right-3`.
- Badge "En carrito" (móvil) en `absolute top-2 left-2 sm:top-3 sm:left-3`.

### 2. Zona de Información
En `p-3 sm:p-4`:
- Fila superior: Código del producto (izquierda) + Botón `[ ℹ️ DETALLES ]` (derecha) con `flex items-center justify-between gap-2 mb-1.5`.
- Fila de Nombre: Nombre truncado en 2 líneas con tipografía clara y buen peso.
- Fila de Precio / Stock restante: Base de la tarjeta.
