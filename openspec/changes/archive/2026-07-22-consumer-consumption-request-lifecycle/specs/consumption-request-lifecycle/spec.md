## ADDED Requirements

### Requirement: Aprobación obligatoria antes del primer despacho

El sistema SHALL permitir despachar una solicitud de consumo solo cuando su estado sea `aprobado` o `despachado_parcial`. El despacho desde `pendiente` o `observado` MUST ser rechazado en backend y la UI MUST NOT ofrecer la acción de despacho en esos estados.

#### Scenario: Almacén intenta despachar solicitud pendiente
- **WHEN** un usuario con rol Almacén intenta despachar una solicitud en estado `pendiente`
- **THEN** el sistema MUST rechazar la operación con error de estado no permitido
- **AND** MUST NOT cambiar el stock ni el estado de la solicitud

#### Scenario: Almacén despacha solicitud aprobada
- **WHEN** un usuario con rol Almacén despacha una solicitud en estado `aprobado`
- **THEN** el sistema MUST permitir el despacho según las reglas existentes de cantidades y stock
- **AND** MUST actualizar el estado a `despachado` o `despachado_parcial` según corresponda

#### Scenario: Completar despacho parcial
- **WHEN** una solicitud está en estado `despachado_parcial`
- **THEN** Almacén MUST poder continuar despachando el restante sin nueva aprobación

#### Scenario: UI oculta despacho si no está aprobada
- **WHEN** un usuario Almacén ve el detalle de una solicitud en estado `pendiente`
- **THEN** el botón "Despachar Stock" y la columna de despacho MUST NOT mostrarse como acción habilitada para despachar desde pendiente

### Requirement: Flujo de recepción cierra el ciclo del Consumidor

El Consumidor SHALL poder confirmar recepción solo en estados `despachado` o `despachado_parcial`. Tras una recepción exitosa el estado MUST quedar en `entregado` y el ciclo del Consumidor para esa solicitud finaliza.

#### Scenario: Consumidor confirma recepción
- **WHEN** el Consumidor confirma la recepción de una solicitud despachada
- **THEN** el estado MUST ser `entregado`
- **AND** MUST registrarse `received_by_user_id` y `received_at`

### Requirement: Creación de solicitudes no se modifica

Este change MUST NOT alterar el endpoint, validaciones, UI ni notificaciones del flujo de creación (`store` / create) de solicitudes de consumo.

#### Scenario: Crear solicitud permanece igual
- **WHEN** un Consumidor crea una solicitud de consumo
- **THEN** el comportamiento de creación MUST ser el existente previo a este change
