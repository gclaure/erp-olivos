## Why

En la vista de catálogo y solicitud de consumos (`/admin/consumption-requests/create` / POS), los usuarios necesitan consultar detalles adicionales del producto (como marca, unidad de medida, presentación por paquete, categorías, ubicación y disponibilidad) antes o durante la adición al carrito. Actualmente la tarjeta de producto solo muestra el nombre y código, sin una forma rápida de ver su ficha técnica completa.

## What Changes

- Se agrega un botón de acción rápida (icono de información/vista rápida) en la tarjeta de producto (`ProductCard.vue`), con evento detenido (`@click.stop`) para no forzar la adición inmediata.
- Se crea el componente modal `ProductDetailModal.vue` en `resources/js/Pages/Admin/POS/Partials/` para visualizar la ficha técnica completa del producto:
  - Imagen en alta resolución / preview limpio.
  - Nombre completo, código y marca.
  - Tipo (Insumo / Materia Prima).
  - Categorías asignadas.
  - Unidad de medida y presentación por paquete (unidades por paquete y nombre de paquete).
  - Ubicación física en almacén (si aplica).
  - Estado de disponibilidad / Stock según el rol (respetando la regla de "Disponible / No disponible" para rol Consumidor).
  - Stepper de cantidad y botón *"Agregar al Pedido"* para añadir directamente al carrito desde el modal.
- Se conecta el evento entre `ProductCard.vue`, `ProductCatalog.vue` e `Index.vue` para abrir el modal con el producto seleccionado.

## Capabilities

### New Capabilities
- `pos-product-detail-modal`: Modal de vista previa y consulta de ficha técnica de productos en el catálogo de solicitudes de consumo / POS.

### Modified Capabilities
<!-- Sin modificaciones en specs de requerimientos funcionales previos -->

## Impact

- Frontend:
  - `resources/js/Pages/Admin/POS/Partials/ProductCard.vue`
  - `resources/js/Pages/Admin/POS/Partials/ProductCatalog.vue`
  - `resources/js/Pages/Admin/POS/Partials/ProductDetailModal.vue` (Nuevo)
  - `resources/js/Pages/Admin/POS/Index.vue`
