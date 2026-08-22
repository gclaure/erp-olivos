## 1. Backend: Exención de Stock en FormRequest y Resource

- [x] 1.1 Modificar `withValidator` en `SaveConsumptionRequest.php` para omitir la validación de stock disponible si `$product->isInventoriable()` es `false`
- [x] 1.2 Actualizar `ConsumptionRequestResource.php` para no marcar `has_missing_stock` en productos no inventariables (Insumos)
- [x] 1.3 Agregar `product_type` e `is_inventoriable` en `ConsumptionRequestDetailResource.php`

## 2. Frontend: Validación de Carrito para Insumos

- [x] 2.1 Modificar `isStockExceeded` en `CartSidebar.vue` para omitir validación de stock en productos tipo `insumo`
- [x] 2.2 Agregar badge visual `Insumo` en los ítems del carrito en `CartSidebar.vue`

## 3. Verificación y Pruebas

- [x] 3.1 Probar creación y listado de solicitudes de consumo con insumos sin mostrar alertas de Falta Stock
- [x] 3.2 Compilar assets con `pnpm build`
