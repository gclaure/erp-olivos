# Proposal: Filtro de Insumo / Materia Prima en Creación de Solicitudes de Consumo

## Why
En la interfaz de creación de solicitudes de consumo interno (`/admin/consumption-requests/create`), los usuarios necesitan poder filtrar con facilidad los productos entre **Insumos** y **Materias Primas**. Adicionalmente, los productos clasificados como de tipo **Insumo** (no inventariables) deben estar permanentemente activos y seleccionables para usuarios con el rol de **Consumidor**, independientemente de la existencia de stock físico registrado en almacén.

## What Changes
1. **Backend (`PosController::searchProducts`)**:
   - Incluir `'type'` en la cláusula `select` de `Product::query()`.
   - Implementar el filtrado por parámetro `type` (`insumo` o `materia_prima`) en el endpoint `admin.api.pos.products`.
2. **Composable (`useProductSearch.js`)**:
   - Agregar el estado reactivo `selectedType` y sincronizarlo como parámetro en las peticiones Axios.
3. **Catálogo Frontend (`ProductCatalog.vue` & `ProductCard.vue`)**:
   - Renderizar barra de chips/pestañas de filtro de tipo: `Todos`, `Insumos` y `Materia Prima`.
   - Garantizar que los productos tipo `insumo` siempre se muestren con el badge identificador y habilitados para ser agregados por los consumidores.

## Capabilities

### New Capabilities
- `consumption-request-type-filter`: Filtrado dinámico por tipo de producto (Insumo / Materia Prima) y disponibilidad irrestricta de insumos para consumidores.

## Impact
- `app/Http/Controllers/Admin/PosController.php`
- `resources/js/Composables/POS/useProductSearch.js`
- `resources/js/Pages/Admin/POS/Index.vue`
- `resources/js/Pages/Admin/POS/Partials/ProductCatalog.vue`
- `resources/js/Pages/Admin/POS/Partials/ProductCard.vue`
