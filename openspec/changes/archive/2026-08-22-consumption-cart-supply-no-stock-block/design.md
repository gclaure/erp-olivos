# Design: Deshabilitar Bloqueo de Stock y Alertas de Falta Stock para Insumos

## Architecture & Data Flow

```
1. Frontend (CartSidebar.vue):
   - isStockExceeded: retorna false si item.type === 'insumo' || !item.is_inventoriable
   - Badge "Insumo" visible en carrito

2. Backend (SaveConsumptionRequest.php):
   - withValidator: omite verificación de stock disponible si !$product->isInventoriable()

3. Resource (ConsumptionRequestResource.php):
   - has_missing_stock:
     - Itera sobre details.
     - Si $detail->product && !$detail->product->isInventoriable() -> continúa sin marcar falta de stock.
     - Solo evalúa $pending > $stock para productos inventariables (Materia Prima).
```

## Component Changes
- `app/Http/Resources/ConsumptionRequestResource.php`:
  - Excluir insumos de `has_missing_stock`.
- `app/Http/Resources/ConsumptionRequestDetailResource.php`:
  - Incluir `product_type` e `is_inventoriable`.
- `app/Http/Requests/Admin/SaveConsumptionRequest.php`:
  - Omitir validación de stock para insumos.
- `resources/js/Pages/Admin/POS/Partials/CartSidebar.vue`:
  - Exención de stock y badge para insumos.
