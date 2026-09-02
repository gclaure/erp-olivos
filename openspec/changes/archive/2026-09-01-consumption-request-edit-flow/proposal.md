## Why

Actualmente los usuarios que registran una solicitud de consumo interno no pueden modificarla una vez guardada, incluso si la solicitud aún se encuentra en estado "Pendiente" esperando la aprobación de Administración. Si el solicitante cometió un error o necesita agregar o retirar insumos antes de que sea aprobada, se ve forzado a solicitar la cancelación o crear una solicitud duplicada.

## What Changes

- Permitir la edición de una solicitud de consumo únicamente cuando su estado es `pendiente` y no ha sido aprobada (`approved_at` es nulo).
- Restringir la edición exclusivamente al usuario que creó la solicitud (`user_id === auth()->id()`).
- Agregar el botón `[ ✏️ Editar Solicitud ]` en la vista de detalle `Show.vue` para el creador cuando la solicitud sea editable.
- Crear la ruta y vista de edición `/admin/consumption-requests/{id}/edit` utilizando la interfaz de catálogo POS precargada con los productos existentes en el carrito lateral y las notas de la solicitud.
- Implementar el endpoint `PUT /admin/consumption-requests/{id}` y el método en `ConsumptionRequestService` para sincronizar productos (crear nuevos detalles, actualizar cantidades solicitadas y eliminar ítems quitados).
- Bloquear de forma estricta cualquier intento de edición una vez que la solicitud haya sido aprobada (`approved_at` no nulo) o cambie de estado.

## Capabilities

### New Capabilities
- `consumption-request-edit-flow`: Flujo de edición integral de solicitudes de consumo en estado pendiente exclusivo para el solicitante propietario.

### Modified Capabilities

## Impact

- Backend: `ConsumptionRequestController.php`, `ConsumptionRequestService.php`, `ConsumptionRequestResource.php`, `routes/admin.php`.
- Frontend: `Show.vue`, `resources/js/Pages/Admin/POS/Index.vue`.
