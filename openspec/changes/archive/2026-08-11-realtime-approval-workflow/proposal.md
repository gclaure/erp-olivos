## Why

Los elementos de estado en las solicitudes de consumo (badge "Pendiente de Aprobación", banners de estado, etc.) solo se actualizan con recarga de página completa. Cuando un admin aprueba, almacén despacha o consumidor recibe, los demás usuarios no ven el cambio hasta recargar. Esto genera confusión, mensajes duplicados y falta de sincronización entre roles.

## What Changes

- Agregar eventos broadcast específicos para cada transición de estado del ciclo de vida de solicitudes de consumo: aprobación, despacho y recepción
- Cada transición MUST disparar un evento WebSocket que actualice el estado en tiempo real en la vista `Show.vue` de Consumption Requests
- Cuando el Consumidor confirma recepción, MUST enviarse una notificación push (WebSocket + campana) al/Administrador de la sucursal y super admins
- La UI de `Show.vue` MUST reaccionar a los eventos WebSocket actualizando badges, banners y paneles de acción sin recarga de página
- NOTA: Purchase Orders NO se incluyen en este cambio (solo Consumption Requests)

## Capabilities

### New Capabilities

- `realtime-approval-websocket`: Eventos WebSocket específicos para cada transición de estado del ciclo de vida (aprobación, despacho, recepción) con actualización reactiva en la UI del detalle de solicitud

### Modified Capabilities

- `consumption-request-notifications`: Agregar requirement de que la notificación de recepción MUST enviarse vía WebSocket (campana + notificación del browser) al administrador, no solo como notificación de base de datos
- `consumer-request-detail-ui`: Agregar requirement de que los banners de estado, badges y paneles de acción MUST actualizarse en tiempo real vía WebSocket sin recarga de página

## Impact

- **Backend**: Modificar `ConsumptionRequestController` para despachar eventos específicos por transición (o refinar el evento `ConsumptionRequestUpdated` existente). Agregar notificación WebSocket al admin en `receive()`
- **Frontend**: Modificar `ConsumptionRequest/Show.vue` para escuchar eventos específicos por tipo de transición y actualizar el estado reactivo. Agregar listener de notificaciones para el admin en `Show.vue`
- **Eventos**: Posible creación de eventos adicionales (`ConsumptionRequestApproved`, `ConsumptionRequestDispatched`, `ConsumptionRequestReceived`) o refinar el existente con campo `action`
- **Dependencias**: No se agregan dependencias nuevas; se usa la infraestructura Reverb + Echo ya configurada
