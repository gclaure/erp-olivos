## 1. Frontend Unrestricted Consumption Adjustments

- [x] 1.1 Eximir `isStockExceeded` y alertas de stock de bloquear el carrito en `CartSidebar.vue` cuando `operationType === 'consumption'`
- [x] 1.2 Permitir seleccionar cantidades libres y agregar al pedido en `ProductDetailModal.vue` cuando `operationType === 'consumption'`, independientemente del stock disponible
- [x] 1.3 Incluir array `stocks` en el formateo de `cartItems` en `ConsumptionRequestController::edit()`

## 2. Build & Verification

- [x] 2.1 Compilar assets con `npm run build`
- [x] 2.2 Verificar en `/admin/consumption-requests/{id}/edit` y `/admin/consumption-requests/create` que los productos cargan y se pueden agregar/modificar libremente sin bordes rojos ni botones bloqueados
