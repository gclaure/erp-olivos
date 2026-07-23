## Why

El botón "Cancelar Solicitud" actualmente es visible para usuarios con rol Almacén/Admin en estados `pendiente`, `aprobado` y `observado`. Según las reglas de negocio, solo el usuario tipo Consumidor puede cancelar una solicitud que él mismo creó. Mostrar el botón a otros roles genera confusión y permite cancelaciones no autorizadas.

## What Changes

- Restringir el botón "Cancelar Solicitud" para que SOLO sea visible cuando el usuario tiene rol Consumidor
- Ocultar el botón completamente para roles Almacén, Admin/Administrador y super_admin, sin importar el estado de la solicitud

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `consumer-request-detail-ui`: Actualizar el escenario de "Cancelar solo en pendiente para Consumidor" para que explícitamente especifique que ningún otro rol debe ver el botón Cancelar

## Impact

- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`: Condición `v-if` del botón Cancelar Solicitud (línea ~1665)
- Spec `consumer-request-detail-ui`: Requisito "Cancelar solo en pendiente para Consumidor"
