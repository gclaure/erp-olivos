## Context

El sistema gestiona inventario con valoración (FIFO / Promedio Ponderado) y registro de movimientos en Kardex para todos los productos de manera uniforme. Sin embargo, en el flujo operativo existen ítems como insumos y suministros menores que se compran y consumen directamente sin requerir control de existencias ni trazabilidad en Kardex. Además, la carga ágil de productos requiere que el código sea opcional y autogenerado mediante SKU tanto en interfaz web como en importación masiva por Excel.

## Goals / Non-Goals

**Goals:**
- Implementar el enum `App\Enums\ProductType` (`materia_prima` e `insumo`).
- Agregar la columna `type` a la tabla `products` con valor por defecto `'materia_prima'` y migrar datos existentes.
- Hacer que el código de producto `code` sea opcional en validación y generar SKU automáticamente si viene vacío.
- Actualizar `ProductTemplateExport` (añadiendo columna `tipo`) y `ProductImport` (procesando tipo y autogenerando SKU en filas sin código).
- Validar `min_stock` como obligatorio únicamente cuando `type === 'materia_prima'`.
- Omitir la creación/actualización de `Stock` y la llamada a `KardexService` en compras (`PurchaseService`) y consumos (`ConsumptionRequestDispatchService`) cuando el producto es `insumo`.
- Adaptar las vistas Vue 3 (`ProductModal.vue`, `Index.vue` y catálogo de consumo) para soportar la selección, visualización y reglas de validación de cada tipo.

**Non-Goals:**
- No se incorporan tipos adicionales (como producto terminado o servicio) en esta iteración.
- No se altera la estructura contable de cuentas por pagar para insumos en compras.

## Decisions

### 1. Enum PHP 8.2 + Cast Eloquent
- **Decisión**: Crear `App\Enums\ProductType: string` con casos `RAW_MATERIAL = 'materia_prima'` y `SUPPLY = 'insumo'`.
- **Razón**: Proporciona tipado estricto, autocompletado y validación nativa con `Rule::enum(ProductType::class)`.

### 2. Código Opcional y Generación de SKU
- **Decisión**: En `StoreProductRequest`, `UpdateProductRequest` y `ProductImport`, el campo `code` es `nullable`. Si está vacío, se invoca `ProductService::generateSku($name, $categoryIds)`.
- **Razón**: Evita fricción en la captura de datos y estandariza la nomenclatura de SKUs corporativos.

### 3. Actualización de Plantilla e Importador Excel
- **Decisión**: En `ProductTemplateExport`, añadir columna `tipo` con validación de lista (`MATERIA_PRIMA`, `INSUMO`). En `ProductImport`, sanitizar el valor ingresado asignando por defecto `materia_prima` si viene en blanco o no coincide.
- **Razón**: Permite la carga masiva consistente de ambos tipos de ítems.

### 4. Validación Condicional en Form Requests
- **Decisión**: En `StoreProductRequest` y `UpdateProductRequest`:
  - `'type' => ['required', Rule::enum(ProductType::class)]`
  - `'min_stock' => ['required_if:type,materia_prima', 'nullable', 'numeric', 'min:0']`
  - `'warehouse_ids' => ['required_if:type,materia_prima', 'array']`
- **Razón**: Garantiza integridad a nivel backend sin depender exclusivamente de las validaciones en Vue.

### 5. Patrón de Bypass en Servicios (`PurchaseService` y `ConsumptionRequestDispatchService`)
- **Decisión**: Antes de invocar a `KardexService::record()` y registrar/actualizar el registro en `stocks`, comprobar `if ($product->type === ProductType::RAW_MATERIAL)`.
- **Razón**: Centraliza y aísla la regla sin sobrecargar `KardexService` con lógica de productos no inventariables.

### 6. Experiencia de Usuario Responsiva en Vue 3
- **Decisión**: En `ProductModal.vue`, selector interactivo (segmented toggle / radio cards) que ajusta la interfaz de inmediato:
  - Al seleccionar **Materia Prima**: El campo `min_stock` se muestra y resalta como requerido (*).
  - Al seleccionar **Insumo**: El campo `min_stock` se deshabilita/oculta con badge informativo *"No inventariable (sin stock)"*.
  - El campo `code` muestra placeholder *"Autogenerado si se deja vacío"*.
- **Decisión en Catálogo**: En `Index.vue`, mostrar badges temáticos (`bg-indigo-100 text-indigo-800` para Materia Prima, `bg-amber-100 text-amber-800` para Insumo) y stock "—" / "N/A" para insumos.

## Risks / Trade-offs

- **[Riesgo]** Importación de Excel con múltiples filas sin código que generen colisión de SKU.
  - *Mitigación*: En `ProductImport`, actualizar o consultar el correlativo anual secuencialmente para cada fila procesada.
