## Context

El POS fue concebido inicialmente para ventas directas (`sale`), donde despachar stock que no existe físicamente bloquea la venta. Al adaptar la interfaz para consumos (`consumption`), quedaron activos validadores de stock restrictivos (`isStockExceeded` y `hasStock`) que bloquean la creación o edición de solicitudes cuando un producto no tiene stock físico disponible.

## Goals / Non-Goals

**Goals:**
- Ajustar `isStockExceeded` en `CartSidebar.vue` para que retorne siempre `false` cuando `props.operationType === 'consumption'`.
- Modificar el botón de envío en `CartSidebar.vue` para que nunca se deshabilite por falta de stock cuando `operationType === 'consumption'`.
- En `ProductDetailModal.vue`, condicionar `hasStock` y `maxQty` para que en modo `consumption` siempre permita seleccionar cualquier cantidad y agregarla al pedido.
- En `ConsumptionRequestController::edit()`, cargar e incluir la relación `stocks` completa en cada ítem del payload `cartItems`.

**Non-Goals:**
- No alterar la validación estricta de stock para ventas directas (`operationType === 'sale'`).

## Decisions

### 1. Desacople de Validación en `CartSidebar.vue`
```javascript
const isStockExceeded = (item) => {
    // En solicitudes de consumo nunca se bloquea por stock (el área operativa pide lo que necesita)
    if (props.operationType === 'consumption') {
        return false;
    }
    if (item.type === 'insumo' || item.is_inventoriable === false) {
        return false;
    }
    const itemWarehouseId = item.warehouse_id || page.props.initialConfig?.activeWarehouseId;
    const stockObj = (item.stocks || []).find(s => s.warehouse_id === itemWarehouseId);
    const physicalStock = stockObj ? parseFloat(stockObj.quantity) : 0;
    const reservedStock = parseFloat(item.reserved_quantity || 0);
    const availableStock = Math.max(0, physicalStock - reservedStock);
    const requestedStock = parseFloat(item.quantity || 0);
    return requestedStock > availableStock;
};
```

### 2. Permitir Adición en `ProductDetailModal.vue`
```javascript
const canAddToCart = computed(() => {
    if (props.operationType === 'consumption') return true;
    return hasStock.value;
});
```

### 3. Payload Enriquecido en `ConsumptionRequestController::edit`
Incluir `stocks => $product->stocks->map(fn($s) => ['warehouse_id' => $s->warehouse_id, 'quantity' => (float)$s->quantity])`.
