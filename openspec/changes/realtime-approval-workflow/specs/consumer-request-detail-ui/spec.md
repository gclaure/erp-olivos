## ADDED Requirements

### Requirement: Show.vue actualiza UI en tiempo real via WebSocket

El componente `ConsumptionRequest/Show.vue` SHALL escuchar eventos WebSocket en el canal `sucursal.{branchId}` y actualizar el estado reactivo `request.value` inmediatamente al recibir un evento `consumption-request.updated`, incluyendo todos los badges, banners, y paneles de acción, sin necesidad de recarga de página.

#### Scenario: Consumidor ve transición de pendiente a aprobado en tiempo real

- **WHEN** un usuario con rol Consumidor está visualizando el detalle de una solicitud en estado `pendiente`
- **AND** el Admin aprueba la solicitud desde otra pestaña/dispositivo
- **THEN** el badge MUST cambiar de "Pendiente" a "Aprobado" inmediatamente
- **AND** el banner "Pendiente de Aprobación" MUST ser reemplazado por "En Espera de Despacho"
- **AND** el botón "Cancelar Solicitud" MUST desaparecer (ya no aplica en `aprobado`)

#### Scenario: Consumidor ve transición de aprobado a despachado en tiempo real

- **WHEN** un usuario con rol Consumidor está visualizando el detalle de una solicitud en estado `aprobado`
- **AND** Almacén completa el despacho desde otra pestaña/dispositivo
- **THEN** el badge MUST cambiar a "Despachado" o "Despachado Parcial"
- **AND** MUST aparecer la acción "Confirmar Recepción"

#### Scenario: Admin ve transición de despachado a entregado en tiempo real

- **WHEN** un usuario con rol Admin está visualizando el detalle de una solicitud en estado `despachado`
- **AND** el Consumidor confirma la recepción desde otra pestaña/dispositivo
- **THEN** el badge MUST cambiar a "Entregado"
- **AND** el panel de acciones MUST actualizarse reflejando el cierre del ciclo

#### Scenario: Almacén ve transición en tiempo real

- **WHEN** un usuario con rol Almacén está visualizando el detalle de una solicitud
- **AND** ocurre cualquier transición de estado (aprobación, recepción, cancelación)
- **THEN** el badge, banners y paneles MUST actualizarse inmediatamente
- **AND** MUST recibir un toast informativo según la transición
