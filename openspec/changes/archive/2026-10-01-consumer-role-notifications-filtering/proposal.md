# Proposal

## Why

El rol Consumidor debe tener un alcance estrictamente acotado a la operativa de sus solicitudes de consumo para evitar sobrecarga de información y exposición indebida de la operativa general del negocio. Actualmente, existen servicios como compras (`PurchaseService`), ajustes de inventario (`MovementService`) y transferencias (`TransferService`) que envían notificaciones a todos los usuarios de la sucursal sin excluir al Consumidor. Asimismo, en el frontend (`ConsumptionRequest/Index.vue`), cuando se crea una solicitud ajena se disparan alertas y toasts ruidosos al consumidor si está en la pantalla de solicitudes. Se requiere aislar de forma estricta las notificaciones que recibe el Consumidor, garantizando que **únicamente** reciba alertas de sus propias solicitudes:
1. Cuando su solicitud es aprobada.
2. Cuando su solicitud es despachada por el almacenero.
3. Cuando su solicitud es cancelada.
4. Cuando su solicitud es modificada en cantidades por el administrador.

## What Changes

- **Exclusión en servicios generales del backend:** Modificar `PurchaseService`, `MovementService` y `TransferService` para excluir explícitamente a los usuarios con rol `Consumidor` / `consumidor` del envío masivo de notificaciones de la sucursal.
- **Validación de pertenencia en notificaciones de solicitud de consumo:** Asegurar que las notificaciones dirigidas al consumidor (`SolicitudConsumoAprobadaNotification`, `SolicitudConsumoDespachadaNotification`, `SolicitudConsumoCanceladaNotification`, `SolicitudConsumoModificadaNotification`) se envíen de forma persistente y por socket exclusivamente al usuario creador de dicha solicitud.
- **Filtrado en frontend (`ConsumptionRequest/Index.vue`):** En el listener del evento en tiempo real `.consumption-request.created` sobre el canal `sucursal.{branchId}`, omitir alertas sonoras, toasts e incrementos de badges si el usuario autenticado tiene rol Consumidor (puesto que esa solicitud no es suya).
- **Manejo de campanita (`NotificationBell.vue`):** Incluir los tipos `consumption_request_approved`, `consumption_request_cancelled` y `consumption_request_modified` con sus rutas de redirección e iconos distintivos para que el Consumidor navegue a su solicitud al hacer clic.

## Capabilities

### Modified Capabilities
- `consumption-request-notifications`: Reforzar la especificación de aislamiento del Consumidor, delimitando que solo recibe notificaciones de eventos que afecten a sus propias solicitudes (aprobación, despacho, cancelación y modificación) y ninguna notificación operativa ajena (compras, ajustes de inventario, transferencias o creación de solicitudes de terceros).

## Impact

- **Backend:** `PurchaseService.php`, `MovementService.php`, `TransferService.php`, `ConsumptionRequestController.php`.
- **Frontend:** `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`, `resources/js/Components/Admin/NotificationBell.vue`.
- **Base de datos / Permisos:** Sin cambios estructurales en BD ni migraciones; se utiliza el sistema Spatie Roles & Permissions y Eloquent queries existentes.
