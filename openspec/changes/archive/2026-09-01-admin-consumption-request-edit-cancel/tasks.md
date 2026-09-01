## 1. Notification Classes

- [x] 1.1 Crear la clase `App\Notifications\SolicitudConsumoCanceladaNotification`
- [x] 1.2 Crear la clase `App\Notifications\SolicitudConsumoModificadaNotification`

## 2. Backend Service & Controller Updates

- [x] 2.1 Agregar el método `updateDetailQuantity` en `ConsumptionRequestService` con validación de estado y actualización de cantidad
- [x] 2.2 Actualizar `ConsumptionRequestController@cancel` para restringir acceso exclusivo a Administrador, despachar notificaciones a solicitante y almacén, y emitir socket `'cancelled'`
- [x] 2.3 Implementar `ConsumptionRequestController@updateDetailQuantity` con validaciones de rol, estado y emisión de notificaciones
- [x] 2.4 Registrar la ruta `PUT /admin/consumption-requests/{consumption_request}/details/{detail}` en `routes/admin.php`

## 3. Frontend UI Updates in Show.vue

- [x] 3.1 Actualizar la tabla de productos (desktop y mobile) en `Show.vue` para mostrar el botón de edición de cantidad solicitada cuando el usuario es Administrador y la solicitud está en estado `pendiente` u `observado`
- [x] 3.2 Implementar modal / SweetAlert2 interactivo para capturar la nueva cantidad solicitada y enviar la petición al backend
- [x] 3.3 Modificar el panel de acciones lateral en `Show.vue` para que el botón "Cancelar Solicitud" sea exclusivo del Administrador y se oculte para el rol Consumidor
- [x] 3.4 Actualizar los listeners de WebSockets en `Show.vue` para manejar las acciones `'cancelled'` e `'item_updated'` con toasts informativos en tiempo real

## 4. Verification & Testing

- [x] 4.1 Probar la edición de cantidad solicitada como Administrador y verificar que se actualice el detalle y se envíen las notificaciones
- [x] 4.2 Probar la cancelación de solicitud como Administrador y verificar que se envíen las notificaciones al creador y al almacén
- [x] 4.3 Verificar que un usuario con rol Consumidor o Almacén no pueda editar cantidades ni cancelar la solicitud
