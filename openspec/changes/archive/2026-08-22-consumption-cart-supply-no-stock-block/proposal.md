# Proposal: Deshabilitar Bloqueo de Stock en Carrito, Backend y Badge 'Falta Stock' para Insumos

## Why
Al crear o listar solicitudes de consumo interno (`/admin/consumption-requests` y `/admin/consumption-requests/create`), los productos clasificados como **Insumo** (`type === 'insumo'` o no inventariables) causaban falsos positivos de falta de stock:
1. En el carrito de creación ([`CartSidebar.vue`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/js/Pages/Admin/POS/Partials/CartSidebar.vue)), advertencia `Stock insuficiente: 0 disp.`.
2. En la validación backend ([`SaveConsumptionRequest.php`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/app/Http/Requests/Admin/SaveConsumptionRequest.php)), rechazo por stock disponible 0.
3. En el listado principal ([`ConsumptionRequest/Index.vue`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/js/Pages/Admin/ConsumptionRequest/Index.vue)), la solicitud aparecía marcada con el badge `Falta Stock` y activaba el botón "Generar Orden de Compra", debido a que [`ConsumptionRequestResource.php`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/app/Http/Resources/ConsumptionRequestResource.php) computaba `has_missing_stock = true` sin verificar si los ítems eran materias primas o insumos.

Dado que los insumos no gestionan stock físico ni inventario, **no deben generar alertas de 'Falta Stock' ni bloquear ninguna etapa del flujo**.

## What Changes
1. **Frontend (`CartSidebar.vue`)**:
   - Eximir a los insumos de `isStockExceeded`.
   - Mostrar badge `Insumo` en los ítems del carrito.
2. **Backend (`SaveConsumptionRequest.php`)**:
   - Omitir validación de stock disponible cuando `$product->isInventoriable()` sea falso.
3. **Resource (`ConsumptionRequestResource.php` y `ConsumptionRequestDetailResource.php`)**:
   - Excluir productos no inventariables (`!$detail->product?->isInventoriable()`) del cálculo de `has_missing_stock`.
   - Exponer `product_type` e `is_inventoriable` en los detalles de solicitud.

## Capabilities

### New Capabilities
- `consumption-cart-supply-no-stock-block`: Tratamiento no inventariable de insumos en creación, guardado y listado de solicitudes de consumo (omisión de validaciones y badges de Falta Stock).

## Impact
- `app/Http/Resources/ConsumptionRequestResource.php`
- `app/Http/Resources/ConsumptionRequestDetailResource.php`
- `app/Http/Requests/Admin/SaveConsumptionRequest.php`
- `resources/js/Pages/Admin/POS/Partials/CartSidebar.vue`
