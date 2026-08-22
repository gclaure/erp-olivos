## Why

Actualmente el sistema trata todos los productos como inventariables por igual, exigiendo control de existencias en almacén y registrando movimientos en el Kardex. En los procesos de producción y compras existen dos categorías fundamentales de ítems:
1. **Materia Prima**: Requiere control estricto de inventario físico, stock mínimo obligatorio, alertas de reposición y registro contable en Kardex.
2. **Insumo**: Materiales y suministros operativos (cintas, embalajes, consumibles menores) que no deben controlar stock físico ni generar registros en Kardex, estando siempre disponibles para su solicitud y consumo directo.

Adicionalmente, se requiere flexibilizar la creación e importación de productos haciendo que el código de producto sea opcional (generando un SKU automático cuando esté vacío) y actualizando la plantilla de importación Excel para soportar la clasificación de tipo.

## What Changes

- **Nuevo campo de clasificación en Producto**: Se añade el parámetro `type` al modelo `Product` con los valores `materia_prima` (Materia Prima) e `insumo` (Insumo / No inventariable).
- **Migración de datos existentes**: Todos los productos actuales se migran automáticamente con `type = 'materia_prima'` para preservar su historial de Kardex y stock.
- **Validación condicional de Stock Mínimo**: El campo `min_stock` se vuelve obligatorio para productos de tipo `materia_prima` y opcional/no aplicable para `insumo`.
- **Código de producto opcional con autogeneración de SKU**: El código de producto pasa a ser opcional en formularios y plantillas. Si se omite, se autogenera un código SKU único basado en categoría, nombre y correlativo anual.
- **Actualización de Plantilla e Importación Excel**:
  - Se añade la columna `tipo` (`materia_prima` / `insumo`, por defecto `materia_prima`) en `ProductTemplateExport`.
  - En `ProductImport`, se soporta la lectura de `tipo` y si `codigo_producto` está vacío, se autogenera su SKU.
- **Exclusión de Kardex y Stock para Insumos en Compras**: Al registrar compras (`PurchaseService`), los ítems de tipo `insumo` generan su detalle de compra y cuenta por pagar, pero no crean registros en `stocks` ni movimientos en `kardex`.
- **Disponibilidad permanente en Solicitudes de Consumo**: En el catálogo y despacho de Solicitudes de Consumo (`ConsumptionRequest`), los productos clasificados como `insumo` se muestran siempre disponibles (sin bloqueo por stock cero) y al despacharse no descuentan Kardex ni stock físico.
- **Interfaz de Usuario (Vue 3 / Inertia)**:
  - En `ProductModal.vue`, selector estilizado para elegir entre Materia Prima e Insumo, ocultando o mostrando dinámicamente el campo de stock mínimo y asignación de almacenes, con código opcional.
  - En `Index.vue` del catálogo de productos, badges visuales que diferencian Materia Prima vs Insumo, con filtros por tipo y tratamiento de stock "N/A" para insumos.

## Capabilities

### New Capabilities
- `product-type-classification`: Gestión del tipo de producto (`materia_prima` e `insumo`), reglas de validación de stock mínimo, código opcional con SKU automático y compatibilidad en importación Excel.

### Modified Capabilities
- `consumption-request-lifecycle`: Soporte para solicitud y despacho de productos tipo `insumo` con disponibilidad continua y bypass de Kardex y stock.

## Impact

- **Modelos y BD**: `Product` (nueva columna `type`, enum `ProductType`, scopes y helpers de SKU).
- **Importación/Exportación**: `ProductTemplateExport`, `ProductImport`, `UnifiedImportService`.
- **Servicios**: `ProductService`, `PurchaseService`, `KardexService`, `ConsumptionRequestService`, `ConsumptionRequestDispatchService`.
- **Requests & Resources**: `StoreProductRequest`, `UpdateProductRequest`, `ProductResource`, `ConsumptionRequestResource`.
- **Vistas Vue 3**: `resources/js/Pages/Admin/Product/Index.vue`, `ProductModal.vue`, y selector de productos en solicitudes de consumo.
