## ADDED Requirements

### Requirement: Campo de observación opcional al despachar

El sistema SHALL mostrar un campo de texto libre (textarea) por cada ítem en el formulario de despacho de Almacén. El campo es opcional. El usuario SHALL poder escribir una observación que se envíe al backend junto con las cantidades.

#### Scenario: Almacén escribe observación al despachar

- **WHEN** el usuario de Almacén presiona "Despachar Stock" y escribe una observación en el campo de algún ítem
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `observations: { [detail_id]: "texto" }` en el POST al backend
- **AND** la observación MUST guardarse en `consumption_request_details.observation`

#### Scenario: Almacén despacha sin observación

- **WHEN** el usuario de Almacén presiona "Despachar Stock" sin escribir observación en ningún ítem
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `observations: {}` (objeto vacío o sin las keys sin texto)
- **AND** el backend MUST procesar el despacho sin errores

#### Scenario: Observación limitada a 500 caracteres

- **WHEN** el usuario de Almacén escribe más de 500 caracteres en el campo de observación
- **THEN** el sistema MUST truncar el texto a 500 caracteres antes de enviarlo

### Requirement: Mostrar observación de despacho en el detalle del ítem

El sistema SHALL mostrar la observación de despacho de cada ítem en el detalle de la solicitud (Show.vue) cuando exista contenido. La observación MUST mostrarse con el patrón visual de "Comentarios de Observación" (texto naranja sobre fondo naranja claro, con borde).

#### Scenario: Consumidor ve observación de despacho en desktop

- **WHEN** un usuario abre el detalle de una solicitud
- **AND** un detail tiene `observation` con contenido
- **THEN** MUST ver un bloque con label "Observación de Despacho" y el texto de la observación
- **AND** el bloque MUST usar estilo naranja (text-orange-700, bg-orange-500/5, border-orange-500/10)

#### Scenario: No se muestra observación vacía

- **WHEN** un detail tiene `observation` nulo o vacío
- **THEN** el sistema MUST NOT mostrar el bloque de observación para ese ítem

#### Scenario: Observación visible en mobile

- **WHEN** un usuario ve el detalle en vista mobile
- **AND** un detail tiene `observation` con contenido
- **THEN** MUST ver el bloque de observación con el mismo estilo que desktop

### Requirement: Mostrar observación en el historial de la solicitud

El sistema SHALL mostrar la observación de despacho del Almacén en la sección de historial/timeline de la solicitud, asociada al evento de despacho.

#### Scenario: Observación aparece en timeline

- **WHEN** un usuario ve la sección de historial de una solicitud despachada
- **AND** existe un detail con observation de despacho
- **THEN** MUST ver la observación como parte del entry de despacho en el timeline
- **AND** el texto MUST mostrarse con el mismo patrón visual naranja

#### Scenario: Timeline sin observación

- **WHEN** un usuario ve la sección de historial
- **AND** ningún detail tiene observation
- **THEN** el timeline MUST mostrarse sin menciones de observación
