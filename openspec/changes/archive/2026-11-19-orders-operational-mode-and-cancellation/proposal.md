# Proposal

## Why

El Módulo de Pedidos debe funcionar estrictamente como un registro operativo y técnico de reservas de clientes (diseños, sabores, decoraciones, fotos de referencia, complementos y logística de entrega), desacoplado del cobro y transacciones económicas que corresponden a los módulos de ventas/caja. Además, los administradores necesitan la capacidad operativa de **anular pedidos** cuando una reserva sea cancelada por el cliente o descartada por el negocio, conservando la trazabilidad del motivo y el estado en el historial.

## What Changes

- **Eliminación completa de la sección "3. Información Económica"**:
  - En `/admin/orders/create`: se eliminan los campos de total, descuento, total final, tabla de pagos/anticipos, métodos de pago y notas de abono.
  - En la vista previa en vivo de `/admin/orders/create`: se retira el bloque económico, dejando una tarjeta resumen limpia de la reserva técnica y logística.
  - En `/admin/orders` (Historial): se eliminan las columnas de "Total / Abonado" y "Saldo Pendiente", los filtros por saldo/pagos, los KPIs de "Reservas con Saldo" y "Saldo Total Pendiente", y el modal/acciones de abonos (`OrderPaymentModal`).
  - En el detalle (`OrderDetailModal`) y comprobante PDF: se retiran los cálculos monetarios, enfocando el documento en la orden técnica de producción y entrega.
  - En validación y backend: se elimina `subtotal` como campo obligatorio en `StoreOrderRequest`, se remueve el procesamiento de pagos en `OrderService`.
- **Incorporación de la función de Anulación de Pedidos**:
  - Agregar columna de estado (`status`: `registrado` / `anulado`), fecha de anulación (`cancelled_at`), usuario que anula (`cancelled_by_user_id`) y motivo opcional (`cancellation_reason`) en la entidad `orders`.
  - Endpoint y acción en backend (`OrderService::cancelOrder` y `OrderController::cancel`) para anular un pedido previa confirmación.
  - Control de UI para anular un pedido desde el listado y desde el modal de detalle con SweetAlert2 de confirmación y captura de motivo.
  - Badges visuales de estado en el listado y modal (`REGISTRADO` en índigo/esmeralda vs `ANULADO` en rose con motivo tachado/indicador).
  - Filtro por estado (`Todos`, `Registrados`, `Anulados`) en el listado de pedidos.

## Capabilities

### New Capabilities
- `order-reservation-management`: Gestión operativa centralizada de reservas y pedidos de clientes (datos de cliente, fecha/hora/modalidad de entrega, productos personalizados, sabores, decoraciones, fotografías .webp, complementos y anulación de pedidos), sin carga económica ni cobros.

### Modified Capabilities
<!-- No requirement changes to existing specs in openspec/specs/ -->

## Impact

- **Frontend**:
  - `resources/js/Pages/Admin/Orders/Create.vue`: eliminación de la sección 3 económica, simplificación de la vista previa y botón de guardado.
  - `resources/js/Pages/Admin/Orders/Index.vue`: sustitución de KPIs de saldo por KPIs de reservas activas/anuladas, reemplazo de columnas de montos por columna de Estado/Acción de Anular, eliminación de `OrderPaymentModal`.
  - `resources/js/Pages/Admin/Orders/Partials/OrderDetailModal.vue`: visualización del estado y botón de anular si está activo.
- **Backend**:
  - Migración para agregar `status`, `cancelled_at`, `cancelled_by_user_id`, `cancellation_reason` a la tabla `orders`.
  - `app/Http/Requests/Admin/Order/StoreOrderRequest.php`: remoción de reglas económicas requeridas.
  - `app/Services/OrderService.php` y `app/Facades/OrderFacade.php`: método `cancelOrder(string $orderId, ?string $reason, string $userId)`.
  - `app/Http/Controllers/Admin/OrderController.php`: método `cancel(CancelOrderRequest $request, string $id)`.
  - `resources/views/exports/orders/order-receipt-pdf.blade.php`: plantilla PDF orientada 100% a la comanda técnica y de entrega.
