## ADDED Requirements

### Requirement: Banner de pendiente de aprobación para Consumidor

Cuando el usuario tiene rol Consumidor y la solicitud está en estado `pendiente`, el sistema SHALL mostrar un aviso que indique que la solicitud está pendiente de aprobación del Administrador. El sistema MUST NOT mostrar en ese estado el mensaje de espera de despacho por almacén.

#### Scenario: Consumidor ve solicitud pendiente
- **WHEN** un Consumidor abre el detalle de una solicitud en estado `pendiente`
- **THEN** MUST ver un banner de pendiente de aprobación del Administrador
- **AND** MUST NOT ver el texto que indica que el almacenero está preparando el despacho

### Requirement: Banner de espera de despacho tras aprobación

Cuando el usuario tiene rol Consumidor y la solicitud está en estado `aprobado`, el sistema SHALL mostrar un aviso de espera de despacho por almacén.

#### Scenario: Consumidor ve solicitud aprobada
- **WHEN** un Consumidor abre el detalle de una solicitud en estado `aprobado`
- **THEN** MUST ver un banner de espera de despacho
- **AND** el mensaje MUST indicar que la solicitud fue aprobada y está en preparación de despacho

### Requirement: Panel de acciones del Consumidor sin jerga de Almacén

Para el rol Consumidor, el sistema MUST NOT mostrar el título “Acciones de Almacén”. Las acciones del Consumidor SHALL agruparse bajo un título propio (p. ej. “Mis Acciones”) solo cuando exista al menos una acción disponible.

#### Scenario: Consumidor en pendiente no ve Acciones de Almacén
- **WHEN** un Consumidor ve una solicitud en `pendiente`
- **THEN** MUST NOT ver el encabezado “Acciones de Almacén”

#### Scenario: Consumidor en despachado ve Confirmar Recepción
- **WHEN** un Consumidor ve una solicitud en `despachado` o `despachado_parcial`
- **THEN** MUST ver la acción Confirmar Recepción
- **AND** MUST NOT ver el encabezado “Acciones de Almacén”

### Requirement: Cancelar solo en pendiente para Consumidor

El Consumidor SHALL poder ver la acción Cancelar Solicitud únicamente cuando el estado sea `pendiente`. En estado `aprobado` el Consumidor MUST NOT ver Cancelar.

#### Scenario: Cancelar visible en pendiente
- **WHEN** un Consumidor ve una solicitud en `pendiente`
- **THEN** MUST ver el botón Cancelar Solicitud

#### Scenario: Cancelar oculto tras aprobación
- **WHEN** un Consumidor ve una solicitud en `aprobado`
- **THEN** MUST NOT ver el botón Cancelar Solicitud

### Requirement: Ocultar alerta de insumos faltantes al Consumidor

El sistema MUST NOT mostrar al Consumidor la alerta de insumos faltantes / stock insuficiente en el detalle de la solicitud.

#### Scenario: Consumidor no ve alerta de faltantes
- **WHEN** un Consumidor abre el detalle y hay stock insuficiente
- **THEN** MUST NOT ver el banner “Insumos Faltantes Detectados”
