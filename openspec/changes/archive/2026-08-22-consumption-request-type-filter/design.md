# Design: Filtro de Insumo / Materia Prima en Creación de Solicitudes de Consumo

## Architecture & Data Flow

```
┌────────────────────────────────────────────────────────────────────────┐
│ ProductCatalog.vue (Filtro por Chips / Pestañas)                      │
│ [ Todos ]  [ 🏷️ Insumos ]  [ 📦 Materia Prima ]                        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ emit('update:typeFilter', type)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ useProductSearch(warehouseId, typeFilter)                              │
│ GET /admin/api/pos/products?warehouse_id=X&search=Y&type=Z             │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ PosController::searchProducts(Request $request)                        │
│ - Select: id, name, code, price, type, location, brand, ...            │
│ - Filter: ->when($request->type, fn($q) => $q->where('type', $type))   │
│ - Resource: POSProductResource with type, type_label, is_inventoriable │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ ProductCard.vue                                                        │
│ - isSupply: product.type === 'insumo' || !product.is_inventoriable     │
│ - hasStock: isSupply || availableQty > 0                               │
│ - Permite añadir al carrito inmediatamente a usuarios consumidores     │
└────────────────────────────────────────────────────────────────────────┘
```

## Component Updates
1. `PosController.php`:
   - En `searchProducts()`, añadir `'type'` al `select()` de `Product::query()`.
   - Añadir `->when($request->get('type'), fn($q, $type) => $q->where('type', $type))`.
2. `useProductSearch.js`:
   - Recibir o exponer `typeFilter = ref('')`.
   - Integrar `type: typeFilter.value` en las llamadas a la API y observarlo en el `watch([query, warehouseId, typeFilter])`.
3. `ProductCatalog.vue`:
   - Barra de botones/chips de filtro moderna con soporte Dark/Light mode y transiciones suaves.
   - Pasar `typeFilter` hacia arriba a `Index.vue`.
4. `ProductCard.vue`:
   - Corroborar que `isSupply` maneje `type === 'insumo'` y badges ámbar limpios.
