## Why

Actualmente, el buscador asíncrono de productos en la creación de compras (`/admin/purchases/create`) permite buscar sin haber definido un almacén de destino y lista cualquier producto sin discriminar si es inventariable o insumo. Dado que los insumos no manejan existencias físicas ni Kardex, no deben poder seleccionarse en compras de reposición de almacén, y la búsqueda debe requerir obligatoriamente un almacén previo para mostrar con precisión el stock disponible de cada materia prima en esa ubicación.

## What Changes

- **Bloqueo condicional del buscador de productos en compras**: El input de búsqueda de productos en `/admin/purchases/create` (y órdenes de compra si aplica) permanece deshabilitado hasta que el usuario seleccione un almacén de destino.
- **Filtrado estricto a Materias Primas**: El buscador asíncrono de productos en compras (`ApiSelectController@products`) filtra y devuelve exclusivamente productos de tipo `materia_prima`, excluyendo totalmente los `insumos`.
- **Cálculo y visualización de stock por almacén**: El endpoint recibe `warehouse_id` y calcula el stock disponible específico del almacén seleccionado, mostrándolo en cada ítem del dropdown con badges visuales de disponibilidad.

## Capabilities

### New Capabilities
- `purchase-product-search-filtering`: Capacidad para restringir la selección de productos en el flujo de compras únicamente a materias primas asociadas a un almacén de destino, mostrando su stock disponible en tiempo real.

### Modified Capabilities
<!-- No requirement changes in existing specs -->

## Impact

- **Backend**: `app/Http/Controllers/Api/ApiSelectController.php` (método `products` para soportar filtro `type` o `only_raw_materials` y cálculo de stock por `warehouse_id`).
- **Frontend**: `resources/js/Pages/Admin/Purchase/Create.vue` (y `PurchaseOrder/Create.vue` si aplica) para deshabilitar el input hasta seleccionar almacén y mostrar el stock en el dropdown.
- **Rutas y APIs**: `admin.api.selects.products` query params extendidos (`type`, `warehouse_id`).
