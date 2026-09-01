## Why

En el flujo operativo de solicitudes de consumo interno (`/admin/consumption-requests/{id}`), el Administrador interviene en la etapa de revisión/aprobación. Actualmente, el administrador no tiene la facultad de ajustar la cantidad solicitada (`quantity_requested`) de los productos ni la exclusividad para cancelar solicitudes con el debido aviso al personal operativo. Se requiere que el Administrador pueda editar la cantidad solicitada de los productos y cancelar la solicitud, asegurando que ante cualquier cancelación o ajuste de cantidades se notifique de inmediato tanto al usuario que solicitó el consumo como al personal de almacén de la sucursal.

## What Changes

- Permitir que el rol Administrador (`Admin`, `Administrador`, `is_super_admin`) pueda editar la cantidad solicitada (`quantity_requested`) de un producto en la solicitud de consumo mientras esté en estado de revisión previa (`pendiente`, `observado`).
- Permitir que el rol Administrador pueda cancelar la solicitud de consumo ingresando un motivo obligatorio, restringiendo la cancelación exclusivamente a este rol.
- Enviar notificaciones de base de datos (`database`) y avisos en tiempo real por WebSockets (`NuevaNotificacion` y `ConsumptionRequestUpdated`) tanto al **usuario solicitante** (creador) como a los usuarios con rol **Almacén** de la sucursal cuando el Administrador modifique una cantidad solicitada o cancele la solicitud.
- Actualizar la interfaz de usuario en `Show.vue` para mostrar el botón de edición de cantidad por ítem y el botón de cancelar solicitud de forma accesible y exclusiva para el rol Administrador.

## Capabilities

### Modified Capabilities
- `consumption-request-lifecycle`: El Administrador puede editar la cantidad solicitada de productos y cancelar la solicitud de consumo durante la etapa de aprobación/revisión previa al despacho.
- `consumption-request-notifications`: Notificar tanto al usuario solicitante como al personal de Almacén de la sucursal cuando el Administrador cancela la solicitud o modifica la cantidad solicitada de un producto.

## Impact

- **Backend:** `App\Http\Controllers\Admin\ConsumptionRequestController`, `App\Services\ConsumptionRequestService`.
- **Notificaciones:** Creación de `SolicitudConsumoCanceladaNotification` y `SolicitudConsumoModificadaNotification`.
- **Frontend / Vue:** `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` (interfaz de edición de cantidad y cancelación exclusiva de administrador).
