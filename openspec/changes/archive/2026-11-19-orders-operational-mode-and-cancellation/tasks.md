# Tasks

## 1. Base de Datos y Backend para Anulación y Modo Operacional

- [x] 1.1 Crear y ejecutar migración para agregar `status`, `cancelled_at`, `cancelled_by_user_id` y `cancellation_reason` a la tabla `orders`, verificando la migración con `php artisan migrate`.
- [x] 1.2 Actualizar el modelo `Order` con los nuevos campos en `$fillable`, relación `cancelledBy()` y scopes de estado (`registered`, `cancelled`), verificando tipos y syntax check.
- [x] 1.3 Crear `CancelOrderRequest` y actualizar `StoreOrderRequest` para eliminar `subtotal` como campo obligatorio y permitir guardar sin datos económicos, verificando validación.
- [x] 1.4 Implementar `cancelOrder` en `OrderService` y `OrderFacade`, y actualizar `createOrder` para inicializar el estado en `registrado` con montos por defecto en 0 sin procesar pagos.
- [x] 1.5 Agregar ruta `POST /admin/orders/{id}/cancel` en `routes/admin.php` y el método `cancel()` en `OrderController`, verificando respuesta JSON/redirect.

## 2. Limpieza Económica y Rediseño en Formulario de Creación

- [x] 2.1 Eliminar de `Create.vue` los campos del formulario de la sección 3 (subtotal, descuento, tabla de pagos y métodos de pago), reemplazándolo por notas generales de la reserva.
- [x] 2.2 Actualizar la tarjeta de "Vista Previa de la Reserva" en `Create.vue` para reflejar únicamente datos de cliente, entrega, productos personalizados y complementos sin montos monetarios.
- [x] 2.3 Probar el guardado de una reserva técnica completa desde `/admin/orders/create` y verificar que se inserte en la base de datos con estado `registrado`.

## 3. Historial de Pedidos, KPIs Operativos y Acción de Anular

- [x] 3.1 Actualizar `OrderController@index` para calcular KPIs operacionales (Total Reservas, Entregas Hoy, Reservas Activas, Reservas Anuladas) y soportar filtrado por `status`.
- [x] 3.2 Actualizar `Index.vue` para reemplazar KPIs económicos por KPIs operacionales y eliminar columnas de total/saldo, incorporando columna de Estado (`REGISTRADO` / `ANULADO`) y botón interactivo para anular con SweetAlert2.
- [x] 3.3 Actualizar `OrderDetailModal.vue` para mostrar el badge de estado, motivo y fecha de anulación si aplica, y botón de anular si está activo, eliminando la sección de pagos.
- [x] 3.4 Desvincular y eliminar `OrderPaymentModal.vue` de `Index.vue` verificando que no queden referencias residuales.

## 4. Comanda Técnica PDF y Verificación Integral

- [x] 4.1 Actualizar [`order-receipt-pdf.blade.php`](file:///Users/claure/Documents/LARAVEL/inventory-vue-olivos/resources/views/exports/orders/order-receipt-pdf.blade.php) para suprimir totales monetarios y agregar indicador de estado (`REGISTRADO` o `ANULADO`), verificando generación de PDF.
- [x] 4.2 Compilar assets con `npm run build` y verificar que no existan errores de compilación ni advertencias de sintaxis.
