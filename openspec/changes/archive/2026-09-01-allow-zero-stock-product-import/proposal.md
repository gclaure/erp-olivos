## Why

Actualmente, el servicio de importación masiva (`UnifiedImportService`) rechaza cualquier producto que tenga una cantidad menor o igual a 0 con el error `"Cantidad debe ser estrictamente mayor a 0"`. Sin embargo, es una necesidad operativa frecuente cargar o actualizar el catálogo de productos (código, nombre, categoría, unidad de medida, empaque, costo unitario base) sin registrar un ingreso inicial físico de existencias ni crear compras o registros en el Kardex.

## What Changes

- Permitir la importación de productos cuya `cantidad` sea `0` (o vacía interpretada como `0`).
- Validar que la `cantidad` sea `>= 0` (rechazando solo valores negativos o no numéricos).
- Permitir que la columna `costo_unitario` sea opcional o 0 cuando la `cantidad` sea `0`. Si `cantidad > 0`, el costo unitario sigue siendo obligatorio y mayor o igual a 0.
- Hacer que la columna `fecha_compra` sea **opcional** cuando la `cantidad` es `0`. Si `cantidad > 0`, la fecha de compra sigue siendo obligatoria y no futura.
- En el procesamiento de filas (`processRow`), los productos con stock 0 se crean o actualizan en el catálogo normalmente.
- En la consolidación final (`completeImport`), solo las filas con `cantidad > 0` se envían a `PurchaseService::createPurchase` para generar la compra, el movimiento de stock y el kardex. Si ningún producto en el archivo tiene `cantidad > 0`, no se genera ninguna compra.

## Capabilities

### New Capabilities
- `zero-stock-product-import`: Permite la carga masiva de productos con stock inicial en 0, registrándolos en el catálogo de productos sin generar compras ni movimientos de kardex.

### Modified Capabilities
<!-- No requirement changes in existing specs -->

## Impact

- **Backend:** `App\Services\UnifiedImportService` (métodos `processValidation`, `processRow`, `completeImport` y `executeImport`).
- **Frontend / UI:** `resources/js/Pages/Admin/Product/Partials/ImportModal.vue` y visualización de métricas de importación.
- **Base de Datos:** Los productos se crean/actualizan en `products` y `categories_products`, pero no se generan registros en `purchases`, `purchase_details`, ni `kardex` para los productos con cantidad cero.
