## 1. Base de Datos y Backend Core

- [x] 1.1 Crear el Enum PHP `App\Enums\ProductType` con valores `materia_prima` y `insumo`, incluyendo labels y helpers descriptivos
- [x] 1.2 Crear y ejecutar migración para agregar la columna `type` en la tabla `products` con default `'materia_prima'` e índice
- [x] 1.3 Actualizar el modelo `Product` (fillable, casts, métodos auxiliares `isRawMaterial()`, `isSupply()` y scopes)
- [x] 1.4 Modificar `StoreProductRequest` y `UpdateProductRequest` para permitir `code` opcional (nullable) y validar `type` y `min_stock` condicional
- [x] 1.5 Asegurar que en `ProductController@store` y `ProductController@update` se autogenere el SKU si `code` viene vacío

## 2. Importación y Exportación de Plantilla Excel

- [x] 2.1 Actualizar `ProductTemplateExport` agregando la columna `tipo` (con validación/instrucciones de Materia Prima / Insumo)
- [x] 2.2 Actualizar `ProductImport` y `UnifiedImportService` para procesar la columna `tipo` (asignando por defecto `materia_prima`) y autogenerar SKU en filas sin código

## 3. Servicios de Negocio (Kardex, Compras y Consumos)

- [x] 3.1 Modificar `PurchaseService` para que al registrar compras de productos tipo `insumo` se cree el detalle y cuentas por pagar pero se omita `Stock` y `KardexService::record()`
- [x] 3.2 Modificar `ConsumptionRequestDispatchService` para permitir despachar insumos sin descontar stock físico ni registrar salidas en Kardex
- [x] 3.3 Actualizar `ProductResource` para exponer `type`, etiqueta legible y bandera de inventariable

## 4. Frontend (Vue 3 / Inertia / Tailwind)

- [x] 4.1 Actualizar `ProductModal.vue` agregando selector de tipo de producto, indicando que el código es opcional y alternando dinámicamente la obligatoriedad y visibilidad de `min_stock` y almacenes
- [x] 4.2 Actualizar `Index.vue` de Productos para incluir filtro por tipo, badges visuales (`Materia Prima` vs `Insumo`) y visualización de stock adecuado ("—" / "N/A" para insumos)
- [x] 4.3 Adaptar el selector de productos en Solicitudes de Consumo para que los insumos figuren siempre disponibles para selección

## 5. Verificación y Calidad

- [x] 5.1 Validar la correcta migración de productos existentes a `materia_prima`
- [x] 5.2 Verificar creación de producto sin código manual comprobando que se autogenera el SKU
- [x] 5.3 Probar descarga de plantilla Excel, llenado e importación masiva con insumos y filas sin código
- [x] 5.4 Ejecutar compra de prueba con insumos y comprobar que no altera tabla `stocks` ni `kardex`
- [x] 5.5 Ejecutar solicitud y despacho de consumo de insumos verificando disponibilidad continua
