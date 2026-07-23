## Context

`/admin/consumption-requests/create` reutiliza `Admin/POS/Index` con `operationType: 'consumption'`. El catálogo usa `ProductCard.vue`, que hoy muestra:

- Badge derecho: cantidad numérica `physical − reserved_quantity`
- Badge izquierdo: `Res: N` con reservas **globales** (ventas no entregadas + solicitudes de consumo pendientes del almacén)

La búsqueda de productos es `PosController::searchProducts` → `POSProductResource`. El rol llega al frontend vía `auth.user.roles` (HandleInertiaRequests). Ya existe el patrón `isConsumidorRole` en pantallas de ConsumptionRequest.

## Goals / Non-Goals

**Goals:**

- Consumidor ve disponibilidad binaria (disponible / no disponible), no stock numérico.
- Consumidor ve solo sus reservas pendientes como `Mis reservados: N`.
- Disponibilidad real sigue restando **reservas totales** del almacén (no solo las del usuario).
- Admin y otros roles en la misma UI conservan el comportamiento actual.

**Non-Goals:**

- Cambiar validación de stock en `SaveConsumptionRequest` o despacho.
- Ocultar stock en carrito, show de solicitud, o POS de ventas.
- Filtrar/ocultar productos sin stock (siguen visibles, deshabilitados).
- Cambiar estados de solicitud o modelo de datos de reservas.

## Decisions

### 1. Separar `reserved_quantity` (global) de `my_reserved_quantity` (usuario)

- **Decisión**: Añadir subquery y campo `my_reserved_quantity` en la API POS; no reutilizar `reserved_quantity` para el badge del consumidor.
- **Rationale**: Si el badge usara solo reservas propias en el mismo campo, el frontend calcularía mal la disponibilidad (`physical − reserved`).
- **Alternativa rechazada**: Enviar solo boolean `is_available` y ocultar números — insuficiente para “Mis reservados” y rompe Admin en la misma API.

### 2. Cálculo de `my_reserved_quantity`

- **Decisión**: Suma de `(quantity_requested − quantity_delivered)` en `consumption_request_details` joined a `consumption_requests` donde:
  - `product_id` = producto
  - `warehouse_id` = almacén de la búsqueda
  - `user_id` = usuario autenticado
  - `status IN ('pendiente', 'parcial', 'despachado_parcial', 'compras_generado')` (mismo set que reservas de consumo globales actuales)
- **Rationale**: Alineado con la subquery global existente; solo filtra por dueño.
- **Fuera**: No incluir `sales_reserved` en “Mis reservados” (el consumidor no opera ventas).

### 3. Detección de UI Consumidor en frontend

- **Decisión**: En `ProductCard` (y props desde catálogo si hace falta):
  - `operationType === 'consumption'`
  - Y rol `Consumidor` / `consumidor` desde `usePage().props.auth.user.roles`
- **Alternativa**: Flag `hideStockNumbers` en `initialConfig` desde el controller — útil como refuerzo, no obligatorio si el rol ya está en auth; se puede añadir `isConsumerView: true` en create para claridad y tests.
- **Preferencia**: Combinar rol + operationType (fuente de verdad del rol ya compartida); opcionalmente pasar `isConsumerView` en `initialConfig` para no acoplar ProductCard solo a strings de rol en múltiples sitios — **elegido**: detectar rol en ProductCard como Show/Index de ConsumptionRequest (consistencia del proyecto).

### 4. Presentación UI Consumidor

| Elemento | Comportamiento |
|----------|----------------|
| Badge derecho | Pill/texto verde “Disponible” o rojo “No disponible” (sin número) |
| Badge izquierdo | Si `my_reserved_quantity > 0`: `Mis reservados: N` (estilo naranja existente adaptado) |
| Click / opacity | Igual: solo clickeable si disponible |
| Tooltip | Nombre del producto; sin “Stock disponible: N” |

Admin en consumption: sin cambios visuales.

### 5. Disponibilidad

```
hasStock = max(0, physicalStock − reserved_quantity_global) > 0
```

`reserved_quantity` del resource **no cambia** su semántica (sales + consumption global).

## Risks / Trade-offs

- **[Risk] Subquery extra por producto en search** → Mitigation: misma forma que subqueries actuales (`addSelect` correlacionada); impacto acotado al paginate(20).
- **[Risk] Usuario sin auth en API** → Mitigation: si no hay user, `my_reserved_quantity = 0`.
- **[Risk] Texto largo “Mis reservados: N” en mobile** → Mitigation: tipografía `text-[8px]` / truncado controlado; validar en card aspect 4/3.
- **[Trade-off] Stock sigue en payload JSON** (`stocks`, `max_stock`) → Aceptable: no es UI; ocultar del network sería otro cambio (security through obscurity limitado de todos modos en cliente).

## Migration Plan

1. Desplegar backend + frontend juntos (campo nuevo es aditivo; UI antigua ignora `my_reserved_quantity`).
2. Rollback: revertir commit; sin migraciones de BD.

## Open Questions

- Ninguna bloqueante. Carrito y tooltips de error de stock quedan fuera de alcance salvo feedback posterior.
