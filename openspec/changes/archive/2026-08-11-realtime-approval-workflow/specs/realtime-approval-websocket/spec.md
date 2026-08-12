## ADDED Requirements

### Requirement: Evento WebSocket incluye campo action para cada transición

El sistema SHALL incluir un campo `action` en el payload del evento `ConsumptionRequestUpdated` que identifique la transición de estado que ocurrió. Los valores permitidos MUST ser: `approved`, `dispatched`, `received`, `observed`, `cancelled`, `created`.

#### Scenario: Evento de aprobación incluye action approved

- **WHEN** el sistema despacha `ConsumptionRequestUpdated` tras una aprobación
- **THEN** el payload MUST incluir `action: "approved"`
- **AND** el campo `request` MUST contener el recurso actualizado con status `aprobado`

#### Scenario: Evento de despacho incluye action dispatched

- **WHEN** el sistema despacha `ConsumptionRequestUpdated` tras un despacho
- **THEN** el payload MUST incluir `action: "dispatched"`
- **AND** el campo `request` MUST contener el recurso actualizado con status `despachado` o `despachado_parcial`

#### Scenario: Evento de recepción incluye action received

- **WHEN** el sistema despacha `ConsumptionRequestUpdated` tras una recepción
- **THEN** el payload MUST incluir `action: "received"`
- **AND** el campo `request` MUST contener el recurso actualizado con status `entregado`

#### Scenario: Evento de observación incluye action observed

- **WHEN** el sistema despacha `ConsumptionRequestUpdated` tras una observación
- **THEN** el payload MUST incluir `action: "observed"`
- **AND** el campo `request` MUST contener el recurso actualizado con status `observado`

#### Scenario: Evento de cancelación incluye action cancelled

- **WHEN** el sistema despacha `ConsumptionRequestUpdated` tras una cancelación
- **THEN** el payload MUST incluir `action: "cancelled"`
- **AND** el campo `request` MUST contener el recurso actualizado con status `cancelado`

### Requirement: Show.vue muestra toast diferenciado por acción y rol

El componente `ConsumptionRequest/Show.vue` SHALL mostrar un toast de SweetAlert2 específico para cada transición de estado, adaptando el mensaje según el rol del usuario que visualiza la página.

#### Scenario: Admin ve toast al aprobar solicitud

- **WHEN** un usuario con rol Admin visualiza el detalle de una solicitud
- **AND** recibe un evento WebSocket con `action: "approved"` para esa solicitud
- **THEN** MUST mostrar un toast con título "Solicitud Aprobada" y mensaje descriptivo
- **AND** el toast MUST usar icono `success`

#### Scenario: Consumidor ve toast al recibir despacho

- **WHEN** un usuario con rol Consumidor visualiza el detalle de una solicitud
- **AND** recibe un evento WebSocket con `action: "dispatched"` para esa solicitud
- **THEN** MUST mostrar un toast con título "Solicitud Despachada" y mensaje indicando que el almacén despachó los insumos
- **AND** el toast MUST usar icono `success`

#### Scenario: Admin ve toast cuando consumidor recibe

- **WHEN** un usuario con rol Admin visualiza el detalle de una solicitud
- **AND** recibe un evento WebSocket con `action: "received"` para esa solicitud
- **THEN** MUST mostrar un toast con título "Recepción Confirmada" y mensaje indicando que el consumidor recibió los insumos
- **AND** el toast MUST usar icono `success`

#### Scenario: Almacén ve toast cuando consumidor recibe

- **WHEN** un usuario con rol Almacén visualiza el detalle de una solicitud
- **AND** recibe un evento WebSocket con `action: "received"` para esa solicitud
- **THEN** MUST mostrar un toast con título "Recepción Confirmada" y mensaje detallado incluyendo nombre del consumidor, área y si hay discrepancias
- **AND** el toast MUST usar icono `success`

#### Scenario: Consumidor ve toast al recepcionar

- **WHEN** un usuario con rol Consumidor confirma la recepción de una solicitud
- **AND** recibe un evento WebSocket con `action: "received"` para esa solicitud
- **THEN** MUST mostrar un toast con título "Recepción Confirmada" y mensaje de cierre de ciclo
- **AND** el toast MUST usar icono `success`

#### Scenario: No se muestran toasts duplicados

- **WHEN** un usuario realiza una acción (aprobar, despachar, recepcionar)
- **AND** el evento WebSocket llega a la misma página
- **THEN** el sistema MUST NOT mostrar toast duplicado (la acción ya mostró feedback vía Inertia flash)
- **AND** el listener MUST verificar que el evento viene de otro usuario antes de mostrar toast

### Requirement: Show.vue actualiza badges y paneles sin recarga

El componente `ConsumptionRequest/Show.vue` SHALL actualizar todos los badges de estado, banners informativos y paneles de acción inmediatamente al recibir un evento WebSocket, sin necesidad de recarga de página.

#### Scenario: Badge de estado se actualiza en tiempo real

- **WHEN** un usuario visualiza el detalle de una solicitud con status `pendiente`
- **AND** recibe un evento WebSocket con `action: "approved"` y status `aprobado`
- **THEN** el badge MUST cambiar de "Pendiente" (amber) a "Aprobado" (emerald) inmediatamente
- **AND** el cambio MUST ser visible sin recarga de página

#### Scenario: Banner de estado se actualiza en tiempo real

- **WHEN** un usuario con rol Consumidor visualiza el detalle de una solicitud con status `pendiente`
- **AND** recibe un evento WebSocket con `action: "approved"`
- **THEN** el banner "Pendiente de Aprobación" MUST desaparecer
- **AND** MUST aparecer el banner "En Espera de Despacho"

#### Scenario: Panel de acciones se actualiza en tiempo real

- **WHEN** un usuario con rol Admin visualiza el detalle de una solicitud con status `pendiente`
- **AND** recibe un evento WebSocket con `action: "approved"`
- **THEN** el panel "Acción Administrativa Requerida" con botones Aprobar/Rechazar MUST desaparecer
- **AND** MUST aparecer el panel "Fase 2: Registro de Compra Física" si aplica

#### Scenario: Consumidor ve action panel actualizado al recibir despacho

- **WHEN** un usuario con rol Consumidor visualiza el detalle con status `aprobado`
- **AND** recibe un evento WebSocket con `action: "dispatched"` y status `despachado`
- **THEN** el panel de "Confirmar Recepción" MUST aparecer inmediatamente
