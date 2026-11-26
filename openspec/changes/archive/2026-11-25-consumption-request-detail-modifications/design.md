# Design

## Context

Estados de `ConsumptionRequest`: `pendiente → aprobado → despachado | despachado_parcial → entregado`, más `observado` y `cancelado`. Hallazgos del código que condicionan el diseño:

- `ConsumptionRequestDispatchService::dispatch()` solo escribe `quantity_delivered` (preparación/despacho); **no toca Kardex ni stock**. No existe mecanismo de reservas en la base de datos.
- El egreso de Kardex (`ADJUSTMENT_OUT`) ocurre únicamente en `receiveRequest()`, y esa recepción es de **toda** la solicitud (pasa a `entregado`); no hay recepción parcial por detalle.
- `dispatch()` hoy marca `despachado_parcial` aun cuando no se despachó ninguna unidad, y crea la `PurchaseOrder` con el texto de la solicitud en `notes` (sin clave foránea).
- Estados de `PurchaseOrder`: `pendiente`, `aprobada`, `cancelada`, `completada` (al convertirse en compra). No existe estado "enviada al proveedor" ni recepción parcial de la orden.
- `ConsumptionRequestDetail.product_id` es mutable y no posee historial de sustituciones.

Ver `proposal.md` para la motivación completa.

## Goals / Non-Goals

**Goals:**
- Flujo proponer → autorizar → (devolver) → aplicar a nivel de detalle.
- Distinguir producto sin despachar, unidades preparadas en almacén y unidades entregadas físicamente (situaciones mixtas por cantidades).
- Registro inmutable de devoluciones parciales o totales sin registrar Kardex pre-recepción.
- Resolver explícitamente las compras del producto reemplazado a nivel de línea (`purchase_order_details`).
- Auditar cambios de producto y de cantidad con historial inmutable.
- Separar falta de existencias de despacho parcial (`pending_stock` con ciclo de vida).
- Concurrencia segura mediante orden global de bloqueos (`solicitud → modificación → detalle → compra`) y eventos Reverb solo tras commit.

**Non-Goals:**
- Devolución posterior a la recepción formal (`entregado`) y movimiento Kardex `RETURN` (pertenece a un flujo post-recepción).
- Recepción parcial de órdenes de compra (el sistema no la modela).
- Gestión de incidencias, pérdidas o cobros por unidades físicas no devueltas.
- Reservas formales de stock en base de datos.

## Decisions

### 1. Tablas `consumption_request_modifications` y `consumption_request_modification_returns`

#### Tabla `consumption_request_modifications`
- `id` (UUID, PK)
- `consumption_request_id` (UUID, FK a `consumption_requests`, indexada)
- `consumption_request_detail_id` (UUID, FK a `consumption_request_details`, indexada)
- `from_product_id` (UUID, FK a `products`, indexada)
- `to_product_id` (UUID, FK a `products`, indexada)
- `original_quantity` (decimal/integer, cantidad original del detalle)
- `requested_quantity` (decimal/integer, cantidad solicitada, mayor a cero)
- `quantity_dispatched_before_modification` (decimal/integer, despachada al momento de la solicitud)
- `quantity_prepared` (decimal/integer, unidades en almacén sin salir físicamente)
- `quantity_physically_delivered` (decimal/integer, unidades entregadas físicamente al solicitante)
- `reason` (string/text, justificación del cambio)
- `status` (string, enum: `pendiente`, `aprobada`, `aplicada`, `rechazada`, `cancelada`)
- `requested_by_user_id` (UUID, FK a `users`, indexada)
- `resolved_by_user_id` (UUID, nullable, FK a `users`, indexada)
- `resolved_at` (datetime, nullable)
- `resolution_notes` (text, nullable)
- `purchase_resolutions` (jsonb/text, resoluciones por línea de compra: `purchase_order_detail_id`, `decision`, `notes`)
- `cancelled_by_user_id` (UUID, nullable, FK a `users`)
- `cancelled_at` (datetime, nullable)
- `cancellation_reason` (text, nullable)
- `applied_at` (datetime, nullable)
- Timestamps

Reglas:
- La suma `quantity_prepared + quantity_physically_delivered` debe ser exactamente igual a `quantity_dispatched_before_modification`.
- Si `quantity_dispatched_before_modification == 0`, ambas cantidades son 0.
- `requested_quantity` por defecto es `original_quantity` si solo se cambia el producto.

#### Tabla `consumption_request_modification_returns`
- `id` (UUID, PK)
- `consumption_request_modification_id` (UUID, FK a `consumption_request_modifications`, indexada)
- `returned_quantity` (decimal/integer, cantidad física devuelta en esta entrega)
- `returned_by_user_id` (UUID, FK a `users`, almacenero que recibe)
- `returned_at` (datetime, timestamp de la devolución)
- `return_notes` (text, nullable)
- Timestamps

Reglas:
- Cada registro de devolución es inmutable (no se edita ni borra).
- La suma acumulada de `returned_quantity` no puede exceder `quantity_physically_delivered`.

### 2. Una modificación abierta por detalle e inmutabilidad estricta
- **Índice único parcial en PostgreSQL:**
  ```sql
  CREATE UNIQUE INDEX idx_crm_open_detail
  ON consumption_request_modifications (consumption_request_detail_id)
  WHERE status IN ('pendiente', 'aprobada');
  ```
  Garantiza a nivel de base de datos que no existan dos modificaciones abiertas en simultáneo para un mismo detalle.
- **Inmutabilidad de estados terminales:** Los estados `aplicada`, `rechazada` y `cancelada` son inmutables. Se implementa una restricción de dominio en el modelo y un trigger en PostgreSQL que rechace cualquier `UPDATE` en registros que ya se encuentren en dichos estados.

### 3. Situación física, devoluciones y aplicación

| Situación física | Flujo de aprobación y aplicación |
|---|---|
| **Sin despacho previo** (`quantity_dispatched_before_modification == 0`) | Al aprobar el administrador: se aplica de inmediato. Se actualiza el detalle (`product_id = to_product_id`, `quantity_requested = requested_quantity`, `quantity_delivered = 0`, `fulfillment_status = normal`), la modificación pasa a `aplicada` en la misma transacción y se recalcula el estado de la solicitud. |
| **Solo preparadas en almacén** (`quantity_prepared > 0`, `quantity_physically_delivered == 0`) | Al aprobar el administrador: se cancela la preparación previa (`quantity_delivered = 0`). Como el stock no se descuenta hasta la recepción ni hay reservas, no se altera Kardex. Se actualiza el detalle a `to_product_id`, la modificación pasa a `aplicada` y se habilita la preparación del nuevo producto. |
| **Entregadas físicamente** (`quantity_physically_delivered > 0`) | Al aprobar el administrador: la modificación queda en `aprobada` (en espera de devolución). El detalle **conserva el producto original** pero queda bloqueado con `fulfillment_status = pending_return`. Se cancela la preparación de las unidades que estuviesen solo preparadas (`quantity_delivered` se ajusta a `quantity_physically_delivered`). |

#### Proceso de devolución y aplicación final:
1. **Devolución física parcial:** Si el solicitante devuelve una cantidad menor a `quantity_physically_delivered`, se inserta la fila en `consumption_request_modification_returns`. El detalle **permanece bloqueado** (`pending_return`), el producto original se mantiene y la modificación sigue `aprobada`. No se reinicia el despacho ni se habilita el sustituto.
2. **Devolución física completa:** Cuando `sum(returned_quantity) == quantity_physically_delivered`, el sistema ejecuta la sustitución:
   - Se actualiza el detalle: `product_id = to_product_id`, `quantity_requested = requested_quantity`, `quantity_delivered = 0`, `fulfillment_status = normal`.
   - La modificación pasa a `aplicada` (`applied_at = now()`).
   - Se recalcula el estado de la solicitud con el resolvedor.
3. **Cancelación administrativa por devolución incompleta:** Si la devolución física no se completa, el administrador puede cancelar la modificación (`cancel()`):
   - La modificación pasa a `cancelada` con motivo obligatorio y usuario resolutor.
   - El detalle conserva el `from_product_id`.
   - El detalle recupera `fulfillment_status = normal`.
   - `quantity_delivered` del detalle se reduce únicamente en las unidades efectivamente devueltas registradas en `consumption_request_modification_returns`.
4. **Cero Kardex pre-recepción:** No se generan movimientos de Kardex (`RETURN`) en ningún caso de devolución física en este flujo, ya que antes de la recepción nunca existió un egreso contable en almacén.

### 4. Estado del detalle y resolvedor de estado de solicitud

- `consumption_request_details.fulfillment_status`: enum PHP `ConsumptionDetailFulfillmentEnum` con valores `normal` (por defecto), `pending_stock`, `pending_return`.
- **Ciclo de vida de `pending_stock`:**
  - Se activa cuando `dispatch()` procesa un detalle con existencias insuficientes (`quantity_delivered < quantity_requested`).
  - Vuelve a `normal` automáticamente en despachos posteriores cuando el detalle se despacha o completa.
  - Un detalle completamente despachado o cancelado nunca conserva `pending_stock`.
- `ConsumptionRequestStatusResolver::resolve(ConsumptionRequest $request)`:
  - Si todos los detalles activos tienen `quantity_delivered >= quantity_requested` → `despachado`.
  - Si no, y la suma total de `quantity_delivered` de la solicitud es mayor a 0 → `despachado_parcial`.
  - Si la suma total despachada es 0 → `aprobado` (independientemente de que haya detalles en `pending_stock`).
  - Preserva estados finales `entregado`, `cancelado` u `observado`.

### 5. Compras asociadas por línea y producto, sin duplicados

- Migración:
  - `purchase_orders.consumption_request_id` (UUID nullable, indexada, FK `restrictOnDelete`).
  - `purchase_order_details.consumption_request_detail_id` (UUID nullable, indexada, FK `restrictOnDelete`).
  - `purchase_order_details.cancelled_at` (datetime nullable).
  - `purchase_order_details.cancellation_reason` (text nullable).
- **Evitar duplicados:** Al despachar faltantes, `ConsumptionRequestDispatchService` calcula la necesidad restando las existencias físicas y la suma de cantidades en líneas de compra abiertas (`pendiente` o `aprobada`) que pertenezcan al **mismo detalle y al mismo producto**.
- **Resolución explícita por línea:** Al aprobar una modificación, el administrador debe definir la acción para cada línea de compra abierta asociada al `consumption_request_detail_id` y al `from_product_id`:
  - `cancelar`: Marca la línea como cancelada (`cancelled_at = now()`, `cancellation_reason`). Si todas las líneas de la orden quedan canceladas, la orden pasa a `cancelada`.
  - `mantener`: Requiere nota obligatoria. La compra continúa su curso para el `from_product_id` (nunca se reasigna al nuevo producto).
  - Si la compra ya está `completada`: No requiere resolución (ingresó al almacén).
- La conversión de órdenes de compra a compras reales ignora las líneas canceladas.

### 6. Servicio de modificaciones y orden global de bloqueos

`ConsumptionRequestModificationService`:
- Métodos principales: `request()`, `approve()`, `reject()`, `confirmReturn()`, `cancel()`.
- **Orden estricto de bloqueos (evita Deadlocks):**
  1. `ConsumptionRequest` (`lockForUpdate()`)
  2. `ConsumptionRequestModification` (`lockForUpdate()`, si aplica)
  3. `ConsumptionRequestDetail` (`lockForUpdate()`)
  4. `PurchaseOrder` / `PurchaseOrderDetail` (`lockForUpdate()`, si aplica)
- `dispatch()`, `receiveRequest()` y `cancelRequest()` adoptan este mismo orden de bloqueo.
- `receiveRequest()` y `cancelRequest()` son rechazados con error de dominio si la solicitud posee alguna modificación abierta (`pendiente` o `aprobada`).
- `dispatch()` ignora detalles con modificaciones abiertas o en estado `pending_return`.

### 7. Eventos Reverb tras commit
- Eventos: `ConsumptionRequestModificationRequested`, `ConsumptionRequestModificationResolved`, `ConsumptionRequestModificationReturnRecorded`, `ConsumptionRequestModificationApplied`.
- Todos implementan `ShouldDispatchAfterCommit` para garantizar que no se emitan mensajes websocket si ocurre un rollback en la transacción.

### 8. Interfaz de usuario (Vue 3 + Inertia)
- Modal para el almacenero: campos de solo lectura para producto y cantidades actuales, selector de producto sustituto (activo y misma unidad de medida), inputs para cantidad preparada y cantidad entregada físicamente (con validación de suma contra despachada), motivo.
- Bandeja del administrador: contexto general de la solicitud, comparación de productos (unidad, costo), desglose de situación física y sección de resolución de compras por línea (con inputs para notas de retención).
- Panel de devolución para el almacenero: modal para registrar devoluciones parciales o totales de detalles en `pending_return`.
- Historial en la vista de detalle: tarjeta con la trazabilidad completa (original, solicitado, resolutor, devoluciones y fechas).
- Coherencia cromática: uso estricto de tokens oficiales Olivo (`primary-*`), Crema (`secondary-*`) y semánticos, sin `indigo` ni `purple`.

### 9. Permisos
- `request-consumption-modification`: Rol Almacén / Supervisor.
- `approve-consumption-modification`: Rol Administrador.
- Asignados mediante seeder y migración con `Role` y `Permission`.

## Risks / Trade-offs

- **[Cambio de estado con despacho en 0 → `aprobado`]**: Corrige la anomalía de marcar `despachado_parcial` sin despachar unidades. Requiere verificar vistas y pruebas existentes que asuman la lógica previa.
- **[Compras legadas sin FK]**: Las órdenes previas que solo guardan texto en `notes` no se enlazarán automáticamente. Se muestra advertencia informativa para resolución manual.
- **[Devoluciones físicas incompletas]**: Se mitiga permitiendo la cancelación administrativa por parte del administrador para desatorar el detalle si el solicitante nunca devuelve los productos.
- **[Unidades de medida distintas]**: Se bloquea la modificación entre productos con distinta unidad de medida; el sistema no realiza conversiones de unidades.
