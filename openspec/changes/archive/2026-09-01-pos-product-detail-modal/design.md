## Context

El catálogo de solicitudes de consumo (`/admin/consumption-requests/create` / POS) utiliza `ProductCard.vue` para mostrar cada ítem disponible. Actualmente, al hacer clic en cualquier parte de la tarjeta se dispara la adición directa al carrito. Se requiere incorporar un botón visual no invasivo para consultar la ficha técnica completa del producto en un modal.

## Goals / Non-Goals

**Goals:**
- Añadir un botón circular con icono flotante (`info` o `visibility`) en la esquina superior izquierda de `ProductCard.vue` con `@click.stop="emit('show-detail', product)"`.
- Crear el componente `ProductDetailModal.vue` en `resources/js/Pages/Admin/POS/Partials/ProductDetailModal.vue` con diseño Tailwind responsivo y soporte para dark mode.
- Renderizar:
  - Imagen ampliada / fallback visual.
  - Título, código, tipo (`Insumo` / `Materia Prima`) con badges semánticos.
  - Categorías asignadas.
  - Unidad de medida y empaque (`units_per_package` y `package_name`).
  - Ubicación física y marca (si existen).
  - Disponibilidad según rol (ocultando stock numérico para rol `Consumidor`).
  - Stepper de cantidad y botón *"Agregar al Pedido"*.

**Non-Goals:**
- No se modifican endpoints backend (`POSProductResource` ya contiene todos los campos requeridos).

## Decisions

### 1. Ubicación del botón en `ProductCard.vue`
Se coloca en `top-2 left-2 sm:top-3 sm:left-3` con diseño traslúcido elegante:
```html
<button 
    type="button"
    @click.stop="emit('show-detail', product)"
    class="absolute top-2 left-2 sm:top-3 sm:left-3 z-20 w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-white/90 dark:bg-secondary-800/90 hover:bg-white dark:hover:bg-secondary-700 text-zinc-600 dark:text-secondary-300 shadow-md flex items-center justify-center transition-transform hover:scale-110 active:scale-95 border border-zinc-200/60 dark:border-secondary-600"
    title="Ver detalles del producto"
>
    <span class="material-symbols-outlined text-[16px] sm:text-[18px]">info</span>
</button>
```

### 2. Flujo de eventos
- `ProductCard.vue` emite `show-detail(product)`.
- `ProductCatalog.vue` retransmite `show-detail(product)`.
- `Index.vue` maneja `selectedDetailProduct.value = product` y `showProductDetailModal.value = true`.
- Desde `ProductDetailModal.vue`, al pulsar "Agregar al Pedido", se llama a `addItem(product, qty)` y se cierra el modal.

## Risks / Trade-offs

- **[Risk]** Conflicto con el badge "En carrito" en móvil que usaba `top-2 left-2`.
  - **Mitigación**: Reubicar o integrar de forma armónica el botón de info y el indicador en carrito.
