## ADDED Requirements

### Requirement: Campo de observación opcional en modal de recepción

El sistema SHALL mostrar un campo de texto libre (textarea) dentro del modal SweetAlert de confirmación de recepción. El campo es opcional. El usuario Consumidor SHALL poder escribir una observación que se envíe al backend junto con las cantidades recibidas.

#### Scenario: Consumidor escribe observación al recepcionar

- **WHEN** el Consumidor presiona "Confirmar Recepción" y escribe una observación en el textarea del modal
- **AND** confirma la recepción
- **THEN** el sistema MUST enviar `receive_observations: { [detail_id]: "texto" }` en el POST al backend
- **AND** la observación MUST guardarse en `consumption_request_details.receive_observation`

#### Scenario: Consumidor recepciona sin observación

- **WHEN** el Consumidor presiona "Confirmar Recepción" sin escribir observación
- **AND** confirma la recepción
- **THEN** el sistema MUST enviar `receive_observations: {}` (objeto vacío)
- **AND** el backend MUST procesar la recepción sin errores

#### Scenario: Observación limitada a 500 caracteres

- **WHEN** el Consumidor escribe más de 500 caracteres en el campo de observación
- **THEN** el sistema MUST truncar el texto a 500 caracteres antes de enviarlo

### Requirement: Preservar observación de despacho al recepcionar

El sistema MUST NOT sobreescribir la columna `observation` (observación de despacho de Almacén) al procesar la recepción. La observación del Consumidor MUST almacenarse en una columna separada `receive_observation`.

#### Scenario: Observación de despacho se preserva

- **WHEN** un detail tiene `observation` con contenido (observación de despacho de Almacén)
- **AND** el Consumidor confirma la recepción
- **THEN** `detail.observation` MUST permanecer sin cambios
- **AND** la observación del Consumidor MUST guardarse en `detail.receive_observation`

#### Scenario: Observación de discrepancia va a receive_observation

- **WHEN** la cantidad recibida difiere de la solicitada
- **AND** el Consumidor ingresa observación obligatoria de discrepancia
- **THEN** la observación MUST guardarse en `receive_observation` (no en `observation`)

### Requirement: Mostrar receive_observation en el timeline

El sistema SHALL mostrar la `receive_observation` de cada ítem en la sección de historial/timeline de la solicitud, asociada al evento de recepción.

#### Scenario: Observación de recepción aparece en timeline

- **WHEN** un usuario ve la sección de historial de una solicitud entregada
- **AND** existe un detail con `receive_observation` con contenido
- **THEN** MUST ver la observación como parte del entry de recepción en el timeline
- **AND** el texto MUST mostrarse con patrón visual naranja

#### Scenario: Timeline sin receive_observation

- **WHEN** un usuario ve la sección de historial
- **AND** ningún detail tiene `receive_observation`
- **THEN** el timeline MUST mostrarse sin menciones de observación de recepción
