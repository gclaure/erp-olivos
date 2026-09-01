## 1. Backend Validation & Import Logic

- [x] 1.1 Actualizar `UnifiedImportService::processValidation` para permitir `cantidad >= 0` y rechazar cantidades negativas
- [x] 1.2 Ajustar validación para permitir `costo_unitario` opcional/0 y `fecha_compra` opcional cuando `cantidad = 0` (obligatorios si `cantidad > 0`)
- [x] 1.3 Modificar `UnifiedImportService::completeImport` para filtrar solo detalles con `cantidad > 0` al construir `$purchaseDetails`
- [x] 1.4 Condicionar la creación de `Purchase` en `completeImport` para que se omita si `$purchaseDetails` está vacío
- [x] 1.5 Actualizar el método síncrono `UnifiedImportService::executeImport` con la misma lógica de filtrado de compras

## 2. Verification & Testing

- [x] 2.1 Probar previsualización e importación con un archivo Excel que contenga productos con stock 0 y stock positivo
- [x] 2.2 Probar importación donde todos los productos tengan stock 0 y verificar que no se creen compras ni registros de kardex
- [x] 2.3 Validar que los productos con stock 0 queden creados correctamente en la tabla `products` y visibles en el catálogo
