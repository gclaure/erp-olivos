# Tasks

## 1. Backend: Exclusión de Consumidores en Servicios Operativos

- [x] 1.1 Excluir usuarios con rol `Consumidor` / `consumidor` del envío de notificaciones de compras recibidas en `app/Services/PurchaseService.php` y verificar la consulta con php artisan test o revisión estática
- [x] 1.2 Excluir usuarios con rol `Consumidor` / `consumidor` del envío de notificaciones de ajustes/discrepancias de inventario en `app/Services/MovementService.php` y verificar la consulta
- [x] 1.3 Excluir usuarios con rol `Consumidor` / `consumidor` del envío de notificaciones de discrepancias de transferencias en `app/Services/TransferService.php` y verificar la consulta

## 2. Frontend: Filtrado de Alertas en Tiempo Real y Soporte en Campanita

- [x] 2.1 Filtrar el listener de `.consumption-request.created` en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` para que no emita sonidos ni toasts emergentes si el usuario actual es Consumidor
- [x] 2.2 Agregar soporte a los eventos `consumption_request_cancelled` y `consumption_request_modified` en `resources/js/Components/Admin/NotificationBell.vue` con rutas de redirección e iconografía adecuada

## 3. Validación y Verificación

- [x] 3.1 Ejecutar `npm run build` para certificar la compilación de assets sin errores de sintaxis o empaquetado
- [x] 3.2 Verificar que el flujo de aprobación, despacho, cancelación y modificación notifique al creador de la solicitud sin errores en backend
