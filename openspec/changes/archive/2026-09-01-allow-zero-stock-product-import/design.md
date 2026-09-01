## Context

El sistema de inventario cuenta con un flujo asíncrono y por bloques para la importación masiva de productos vía Excel gestionado por `UnifiedImportService.php` y `ProductController.php`. Actualmente, el validador impone que `cantidad > 0` y que exista `fecha_compra`. Al finalizar la importación, todas las filas procesadas se agregan como ítems de una `Purchase` que automáticamente aumenta el stock en `stocks` y genera asientos en el `kardex`.

## Goals / Non-Goals

**Goals:**
- Permitir la importación de productos con `cantidad = 0` (catálogo inicial o sin existencias).
- Validar `costo_unitario` obligatorio cuando `cantidad > 0`, y opcional/0 cuando `cantidad = 0`.
- Hacer `fecha_compra` obligatoria solo si `cantidad > 0`, y opcional si `cantidad = 0`.
- Excluir del registro de compra y del kardex a todos los productos cuya `cantidad` sea `0`.
- Omitir la creación de la cabecera `Purchase` si ningún producto del lote tiene `cantidad > 0`.
- Mantener compatibilidad con el procesamiento por lotes/chunks y la UI existente de `ImportModal.vue`.

**Non-Goals:**
- Modificar la estructura de base de datos de productos ni añadir campos nuevos.
- Modificar el flujo de compras manuales desde el módulo de compras.

## Decisions

### 1. Modificación de validaciones en `UnifiedImportService::processValidation`
- Validar cantidad: `$qty < 0` genera error `"Cantidad debe ser un número mayor o igual a 0."`. `$qty == 0` es totalmente válido.
- Validar costo unitario: Si `$qty > 0`, `costo_unitario` es obligatorio y no negativo. Si `$qty == 0`, `costo_unitario` es opcional (por defecto 0.0) y no puede ser negativo si se proporciona.
- Validar fecha: Si `$qty > 0`, `$date` es obligatorio y no debe ser futuro. Si `$qty == 0`, si no se suministra fecha se asigna null o se ignora sin error.

### 2. Creación / Actualización de Producto en `processRow`
- Se ejecuta la creación o actualización del `Product` y la sincronización con categorías y unidad de medida normalmente para todas las filas (tanto con `$qty == 0` como `$qty > 0`).
- Se almacena `$qty` en el `ImportLogDetail`.

### 3. Consolidación en `completeImport`
- Se filtran los detalles donde `$data['cantidad'] > 0`.
- Solo estos ítems forman parte de `$purchaseDetails`.
- Si `count($purchaseDetails) > 0`, se llama a `$this->purchaseService->createPurchase(...)` y se asigna el `purchase_id` a las métricas.
- Si `count($purchaseDetails) === 0`, no se genera `Purchase` (no se invoca `createPurchase`), se registra `purchase_id = null` en métricas y el lote se marca como `completed` con éxito.

## Risks / Trade-offs

- **[Riesgo] Importaciones solo de catálogo generan métricas con 0 stock agregado** → *Mitigación:* La UI de `ImportModal.vue` ya muestra productos creados y actualizados por separado del stock agregado, por lo que el usuario verá claramente el resumen de productos ingresados sin confusión.
