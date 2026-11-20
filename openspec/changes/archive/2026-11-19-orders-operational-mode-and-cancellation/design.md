# Design

## Context

El módulo de pedidos actualmente incluye atributos y componentes económicos (`subtotal`, `discount`, `total`, `paid_amount`, `balance_due`, `OrderPayment`, modal de abonos y filtros de saldo). Véase `proposal.md` para la motivación de desacoplar el módulo hacia un enfoque 100% operativo de reservas de clientes e incorporar la capacidad de anulación de pedidos.

## Goals / Non-Goals

**Goals:**
- Retirar completamente la captura, cálculo y visualización de montos monetarios en `Create.vue`, `Index.vue`, `OrderDetailModal.vue` y comprobante PDF.
- Adaptar `Create.vue` para que la sección final sea un resumen visual claro de la reserva técnica y logística.
- Implementar la máquina de estados de anulación (`status`: `registrado` | `anulado`) con registro de auditoría (`cancelled_at`, `cancelled_by_user_id`, `cancellation_reason`).
- Incorporar control interactivo de anulación con SweetAlert2 (confirmación y motivo) en el listado y modal de detalle.
- Adaptar los KPIs del listado a métricas operativas (Total Reservas, Entregas Hoy, Reservas Activas, Reservas Anuladas).

**Non-Goals:**
- No se eliminará la tabla `order_payments` ni se realizarán migraciones destructivas (las columnas numéricas mantendrán su valor por defecto `0.00` para garantizar retrocompatibilidad).
- No se vinculará con descuentos automáticos de inventario ni con el Punto de Venta (POS).

## Decisions

### 1. Estado de Anulación en Base de Datos
- **Decisión**: Añadir a la tabla `orders` las columnas:
  - `status`: string con valor por defecto `'registrado'` (valores: `registrado`, `anulado`).
  - `cancelled_at`: timestamp nullable.
  - `cancelled_by_user_id`: uuid nullable con llave foránea a `users`.
  - `cancellation_reason`: text nullable.
- **Razón**: Permite trazabilidad completa de quién y por qué canceló un pedido, preservando la numeración correlativa ininterrumpida.
- **Alternativa descartada**: Soft-deletes puros (`deleted_at`). Descartado porque ocultaría los pedidos cancelados del historial de los clientes e impediría auditar las razones de cancelación.

### 2. Flujo de Anulación en Backend (SOLID & Facade)
- **Decisión**:
  - Crear FormRequest `CancelOrderRequest` que valide `cancellation_reason` (string, max 500, opcional).
  - Implementar `OrderService::cancelOrder(string $orderId, ?string $reason, string $userId): Order`.
  - Exponer método en `OrderFacade` y controlador `OrderController::cancel(CancelOrderRequest $request, string $id)`.
  - Si el pedido ya está `anulado`, retornar excepción/error de validación HTTP 422.

### 3. Rediseño de UI en `Create.vue`
- **Decisión**: Reemplazar el panel izquierdo de "3. Información Económica" por una sección de "Observaciones y Notas Generales de la Reserva". Mantener en el panel derecho la tarjeta de "Vista Previa de la Reserva" mostrando exclusivamente datos de entrega, productos, especificaciones y complementos, con los botones "Cancelar" y "Guardar Reserva".
- **Razón**: Da al usuario una revisión final inmediata de lo que se enviará al taller/cocina sin la distracción de precios.

### 4. Rediseño de UI en `Index.vue`
- **Decisión**:
  - Reemplazar los KPIs económicos ("Reservas con Saldo" y "Saldo Total") por "Reservas Activas" y "Reservas Anuladas".
  - En la tabla y vista móvil: sustituir las columnas de Total y Saldo por una columna destacada de **Estado** (`REGISTRADO` en verde/indigo, `ANULADO` en rose) y botón de acción directa **Anular Pedido** (con icono `cancel` o `block`).
  - Reemplazar el filtro de saldo por un filtro de estado (`Todos`, `Registrados`, `Anulados`).
  - Eliminar la importación y renderizado de `OrderPaymentModal.vue`.

### 5. Comanda Técnica en PDF
- **Decisión**: Actualizar [`order-receipt-pdf.blade.php`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/views/exports/orders/order-receipt-pdf.blade.php) para eliminar la tabla de totales y saldos. Incluir una marca de agua o badge si el pedido está `ANULADO` y resaltar las especificaciones de preparación y complementos.

## Risks / Trade-offs

- **[Riesgo] Peticiones frontend antiguas que envíen campos económicos** → **Mitigación**: `StoreOrderRequest` tratará cualquier campo económico como nullable / ignorado y `OrderService` forzará 0.00 sin fallar.
- **[Riesgo] Anulación accidental de un pedido** → **Mitigación**: Se requiere confirmación modal explícita mediante SweetAlert2 con advertencia irreversible y campo para ingresar el motivo.
