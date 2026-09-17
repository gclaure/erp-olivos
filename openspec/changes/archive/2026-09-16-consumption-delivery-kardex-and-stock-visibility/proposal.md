## Why

Actualmente, el descuento de stock físico y el registro de movimientos en el Kardex (`ADJUSTMENT_OUT`) se ejecutan durante la fase de Despacho (`dispatchRequest`), lo que genera inconsistencias de inventario si los productos no son entregados al solicitante o la solicitud es cancelada posteriormente. Asimismo, los consumidores se ven impedidos de solicitar insumos cuando el saldo físico en sistema es cero (`0`), bloqueando sus operaciones productivas. Por último, el personal de almacén carece de indicadores visuales inmediatos para conocer la disponibilidad de stock o faltantes de una solicitud antes de procesarla.

## What Changes

- **Postergación del descuento de Kardex a la Entrega Efectiva (`receiveRequest`)**: Mover el decremento en la tabla `stocks` y la inserción del movimiento en `kardex` (`KardexMovementType::ADJUSTMENT_OUT`) a la etapa de confirmación de entrega/recepción, asegurando que el stock físico solo se debite cuando los productos han sido recibidos conforme.
- **Desbloqueo total de solicitudes de consumo sin límite de stock**: Permitir a los consumidores agregar productos al carrito en modo consumo (`operationType === 'consumption'`) independientemente de si el saldo disponible es cero (`0`), eliminando restricciones visuales (`cursor-not-allowed`, badge prohibitivo `Agotado`) y topes artificiales de `maxQty`.
- **Indicadores visuales de disponibilidad y faltantes para el Almacenero**:
  - En el listado (`Index.vue`): Incorporar badges de disponibilidad de stock por solicitud (🟢 Stock Completo 100%, 🟡 Stock Parcial, 🔴 Sin Stock).
  - En el detalle (`Show.vue`): Desplegar un panel de alerta con el desglose exacto de insumos faltantes en almacén y acceso directo para generar la Solicitud de Compra (`PurchaseOrder`).

## Capabilities

### New Capabilities
- `consumption-delivery-kardex-execution`: Especifica que el débito de inventario en `stocks` y el registro cronológico en `kardex` se producen estrictamente durante la entrega/recepción conforme (`receiveRequest`).
- `warehouse-stock-shortage-indicators`: Especifica los indicadores visuales y semáforos de disponibilidad de stock en el listado y detalle de solicitudes de consumo para el rol Almacén.

### Modified Capabilities
- `consumption-unrestricted-stock-request`: Actualizar los requerimientos para que ningún producto sea bloqueado en la interfaz de catálogo para consumidores (`ProductCard.vue` y `ProductDetailModal.vue`).

## Impact

- **Affected code**:
  - Backend: `app/Services/ConsumptionRequestDispatchService.php`, `app/Services/ConsumptionRequestService.php`, `app/Http/Controllers/Admin/ConsumptionRequestController.php`.
  - Frontend: `resources/js/Pages/Admin/POS/Partials/ProductCard.vue`, `resources/js/Pages/Admin/POS/Partials/ProductDetailModal.vue`, `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`, `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`.
- **APIs / Data layer**: Sin alteraciones en esquemas de tablas ni migraciones destructivas.
