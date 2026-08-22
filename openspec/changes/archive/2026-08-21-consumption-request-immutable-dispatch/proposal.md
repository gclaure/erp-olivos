## Why

En la vista de detalle de solicitudes de consumo interno (`/admin/consumption-requests/{id}`), la columna de despacho contiene campos redundantes de observaciones por ítem y dictado por voz que saturan la interfaz. Adicionalmente, el input de cantidad a despachar es editable, lo que permite modificar o aumentar arbitrariamente cantidades previamente aprobadas por administración. Según las reglas de negocio, una solicitud aprobada no debe ser alterada en cantidad; cualquier requerimiento adicional de insumos debe ser tramitado mediante una nueva solicitud desde cero.

## What Changes

- **Inmutabilidad de la Cantidad a Despachar**: El campo de cantidad a despachar se convierte en un indicador fijo/bloqueado (readonly), reflejando exactamente la cantidad pendiente calculada que cubre el stock disponible (`min(pendiente, stock)`), impidiendo cualquier aumento no autorizado en la solicitud.
- **Eliminación de Observaciones por Ítem y Micrófono en Despacho**: Se eliminan los botones `+ Observación`, los `textarea` individuales y los botones de dictado por voz en la columna de despacho tanto en la tabla de escritorio como en las tarjetas móviles.
- **Centralización de Observaciones de Despacho**: Las notas generales de despacho se gestionan exclusivamente en la modal de confirmación final (`Swal.fire`).

## Capabilities

### New Capabilities
- `consumption-request-immutable-dispatch`: Regla de interfaz que bloquea la edición de cantidades en el despacho de solicitudes de consumo y simplifica la columna de despacho eliminando textareas individuales redundantes.

### Modified Capabilities

## Impact

- Frontend: `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`.
- Sin cambios en el backend ni en las migraciones de base de datos.
