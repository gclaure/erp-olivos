# Proposal

## Why

Una solicitud de consumo aprobada puede contener un producto equivocado que solo se descubre cuando el almacenero va a entregar (ej.: se pidió mantequilla con sal y se necesitaba sin sal). Hoy el detalle no puede corregirse después de aprobado, y `ConsumptionRequestDetail.product_id` se sobrescribiría sin historial. Se necesita un flujo controlado en el que el almacenero **proponga** el cambio, el administrador lo **autorice** y el sistema actualice solo ese detalle, sin reiniciar la aprobación de toda la solicitud.

Esta versión incorpora las correcciones de la segunda revisión: devolución parcial, cantidades físicas mixtas, resolución de compras por línea y producto, orden global de bloqueos, ciclo de vida de `pending_stock`, integridad histórica de claves foráneas e inmutabilidad del historial.

## What Changes

- Nueva entidad **Solicitud de modificación de detalle** (`consumption_request_modifications`) con auditoría de producto **y** cantidad: `from_product_id`, `to_product_id`, `original_quantity`, `requested_quantity` (igual a la original si solo cambia el producto), `quantity_dispatched_before_modification`, `quantity_prepared` y `quantity_physically_delivered`.
- **Situación física por cantidades** (no un único estado): al solicitar, el almacenero declara cuántas unidades están preparadas en almacén y cuántas fueron entregadas físicamente; la suma debe igualar la cantidad despachada del detalle. Admite situaciones mixtas.
- Una sola modificación **abierta** (`pendiente` o `aprobada`) por detalle, con índice único parcial en PostgreSQL. Estados finales (`aplicada`, `rechazada`, `cancelada`) **inmutables**: restricción de dominio en el modelo y trigger en la base de datos.
- **Aplicación según la situación física**:
  - Sin unidades entregadas físicamente: al aprobar se cancela la preparación y se aplica el cambio de inmediato (no hay reservas ni Kardex que tocar).
  - Con unidades entregadas físicamente: al aprobar queda `aprobada` esperando devolución y el detalle conserva el producto original, bloqueado (`pending_return`). El cambio se aplica **solo cuando la suma de devoluciones iguala las unidades entregadas**.
- **Devoluciones parciales**: cada devolución se registra como fila inmutable (`consumption_request_modification_returns`). Una devolución parcial **no** reinicia el despacho ni habilita el producto sustituto. Una nota no sustituye una devolución física. El total devuelto no puede superar lo entregado.
- **Cancelación por el administrador** de una modificación `aprobada` con devolución incompleta: el detalle recupera el estado normal con su producto original y su `quantity_delivered` se reduce en las unidades efectivamente devueltas. Incidencias, pérdidas o autorizaciones excepcionales por unidades no devueltas quedan fuera de alcance.
- **Compras asociadas, resueltas por línea y por producto**: las compras generadas por faltante se enlazan a solicitud y detalle con claves foráneas `restrictOnDelete`. Al aprobar, el administrador resuelve cada línea abierta del **mismo detalle y del producto reemplazado**: cancelar la línea o mantenerla con nota. Se cancela a nivel de línea (nuevo `cancelled_at`/`cancellation_reason` en `purchase_order_details`); la orden completa solo pasa a `cancelada` cuando todas sus líneas están canceladas, y la conversión ignora líneas canceladas. Una compra mantenida queda vinculada al producto original; nunca se reasigna al nuevo.
- **Sin duplicados**: `dispatch()` genera compra solo por el faltante no cubierto por líneas abiertas del mismo detalle **y del mismo producto**.
- **`pending_stock` con ciclo de vida**: se marca al despachar sin existencias y vuelve a `normal` cuando el detalle queda completo o recibe unidades en un despacho posterior. Un detalle completo nunca queda en `pending_stock`. La solicitud es `despachado_parcial` solo si se despachó al menos una unidad, y `aprobado` si ninguna (**modifica** el requisito de despacho parcial existente).
- **Orden global de bloqueos** `solicitud → modificación → detalle → compra` en todas las operaciones del flujo (incluidos `dispatch()`, `receiveRequest()` y `cancelRequest()`); las compras también se bloquean al resolverse. Eventos de Reverb después del commit.
- **Validaciones adicionales**: cantidad solicitada mayor que cero, producto destino activo, **misma unidad de medida** (el sistema no convierte unidades), advertencia de diferencia de costo, devolución no mayor a lo entregado.
- **Integridad histórica**: no se eliminan físicamente detalles con compras o modificaciones asociadas.
- Sin movimiento de Kardex: antes de la recepción no existe un egreso que revertir y un `RETURN` sin `OUT` previo inflaría el stock.
- Fuera de alcance: devolución posterior a la recepción (`entregado`), incidencias/pérdidas por unidades no devueltas, recepción parcial de compras (no se modela).

## Capabilities

### New Capabilities
- `consumption-request-detail-modifications`: solicitud, aprobación/rechazo/cancelación, devoluciones físicas parciales y completas, aplicación de modificaciones de producto y cantidad, y resolución de compras por línea, con historial inmutable y concurrencia segura.

### Modified Capabilities
- `consumption-dispatch-reception-stock-flow`: el despacho sin existencias marca el detalle `pending_stock` (y lo limpia al satisfacerse) y la solicitud pasa a `despachado_parcial` solo si se despachó al menos una unidad.

## Impact

- **Base de datos:** migraciones de `consumption_request_modifications` y `consumption_request_modification_returns`; trigger de inmutabilidad; `fulfillment_status` en `consumption_request_details`; `consumption_request_id` en `purchase_orders` y `consumption_request_detail_id`, `cancelled_at`, `cancellation_reason` en `purchase_order_details`; FKs históricas `restrictOnDelete`.
- **Backend:** modelos, enums, `ConsumptionRequestModificationService`, `ConsumptionRequestStatusResolver`, ajustes a `ConsumptionRequestDispatchService`, `ConsumptionRequestService::receiveRequest/cancelRequest` y `PurchaseOrderService` (cancelación por línea, conversión); Form Requests, Resource, controlador, rutas, permisos.
- **Frontend:** modal de solicitud con cantidades físicas, bandeja del administrador con resolución de compras por línea, devoluciones parciales para el almacenero, historial (paleta Olivo/Crema).
- **Comportamiento existente que cambia:** despacho con stock 0 en todos los productos ya no deja la solicitud en `despachado_parcial`; la conversión de órdenes de compra ignora líneas canceladas.
