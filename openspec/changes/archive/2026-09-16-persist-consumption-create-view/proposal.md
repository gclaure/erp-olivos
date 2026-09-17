## Why

Al registrar una nueva solicitud de consumo desde la interfaz de creación (`/admin/consumption-requests/create`), el backend actualmente redirige hacia el listado general (`/admin/consumption-requests`). Esto interrumpe el flujo operativo en las áreas solicitantes o puntos de consumo que requieren registrar múltiples órdenes o consumos de manera ágil y consecutiva. Redirigir de regreso a `/admin/consumption-requests/create` permite mantener al usuario en el formulario de creación listo para la siguiente solicitud, mientras se limpia el carrito y se abre el comprobante PDF en una pestaña independiente.

## What Changes

- **Modificación de redirección posterior al registro (`store`)**: Cambiar la respuesta de `ConsumptionRequestController::store` para redirigir a `admin.consumption-requests.create` en lugar de `admin.consumption-requests.index`.
- **Preservación de datos de flash y apertura de comprobante**: Mantener `success` y `success_data` (con el parámetro `id` de la solicitud creada) para que el frontend (`Admin/POS/Index.vue`) continúe abriendo la impresión del PDF y limpiando el carrito reactivo.

## Capabilities

### New Capabilities
- `consumption-request-creation-redirect`: Especifica el comportamiento de redirección y permanencia en la vista de creación (`create`) tras registrar exitosamente una solicitud de consumo.

## Impact

- **Affected code**: `app/Http/Controllers/Admin/ConsumptionRequestController.php` (método `store`) y `resources/js/Pages/Admin/POS/Index.vue`.
- **APIs / Data layer**: Sin cambios en la base de datos ni en el esquema de validación `SaveConsumptionRequest`.
