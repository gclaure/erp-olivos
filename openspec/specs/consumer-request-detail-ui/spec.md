# Consumer Request Detail UI

Comportamiento visual del detalle de solicitud de consumo (`Show.vue`) según el rol del usuario, con foco en la experiencia del Consumidor.

## Requirement: Banner de pendiente de aprobación para Consumidor

Cuando el usuario tiene rol Consumidor y la solicitud está en estado `pendiente`, el sistema SHALL mostrar un aviso que indique que la solicitud está pendiente de aprobación del Administrador. El sistema MUST NOT mostrar en ese estado el mensaje de espera de despacho por almacén.

### Scenario: Consumidor ve solicitud pendiente

- **WHEN** un Consumidor abre el detalle de una solicitud en estado `pendiente`
- **THEN** MUST ver un banner de pendiente de aprobación del Administrador
- **AND** MUST NOT ver el texto que indica que el almacenero está preparando el despacho

## Requirement: Banner de espera de despacho tras aprobación

Cuando el usuario tiene rol Consumidor y la solicitud está en estado `aprobado`, el sistema SHALL mostrar un aviso de espera de despacho por almacén.

### Scenario: Consumidor ve solicitud aprobada

- **WHEN** un Consumidor abre el detalle de una solicitud en estado `aprobado`
- **THEN** MUST ver un banner de espera de despacho
- **AND** el mensaje MUST indicar que la solicitud fue aprobada y está en preparación de despacho

## Requirement: Panel de acciones del Consumidor sin jerga de Almacén

Para el rol Consumidor, el sistema MUST NOT mostrar el título "Acciones de Almacén". Las acciones del Consumidor SHALL agruparse bajo un título propio (p. ej. "Mis Acciones") solo cuando exista al menos una acción disponible.

### Scenario: Consumidor en pendiente no ve Acciones de Almacén

- **WHEN** un Consumidor ve una solicitud en `pendiente`
- **THEN** MUST NOT ver el encabezado "Acciones de Almacén"

### Scenario: Consumidor en despachado ve Confirmar Recepción

- **WHEN** un Consumidor ve una solicitud en `despachado` o `despachado_parcial`
- **THEN** MUST ver la acción Confirmar Recepción
- **AND** MUST NOT ver el encabezado "Acciones de Almacén"

## Requirement: Cancelar solo en pendiente para Consumidor

El Consumidor SHALL poder ver la acción Cancelar Solicitud únicamente cuando el estado sea `pendiente`. En estado `aprobado` el Consumidor MUST NOT ver Cancelar. Ningún usuario con rol distinto a Consumidor (Almacén, Admin/Administrador, super_admin) SHALL ver el botón Cancelar Solicitud en ningún estado.

### Scenario: Cancelar visible en pendiente

- **WHEN** un Consumidor ve una solicitud en `pendiente`
- **THEN** MUST ver el botón Cancelar Solicitud

### Scenario: Cancelar oculto tras aprobación

- **WHEN** un Consumidor ve una solicitud en `aprobado`
- **THEN** MUST NOT ver el botón Cancelar Solicitud

### Scenario: Almacén no ve Cancelar en ningún estado

- **WHEN** un usuario con rol Almacén ve una solicitud en cualquier estado (`pendiente`, `aprobado`, `observado`, `despachado`)
- **THEN** MUST NOT ver el botón Cancelar Solicitud

### Scenario: Admin no ve Cancelar en ningún estado

- **WHEN** un usuario con rol Admin/Administrador o super_admin ve una solicitud en cualquier estado
- **THEN** MUST NOT ver el botón Cancelar Solicitud

## Requirement: Ocultar alerta de insumos faltantes al Consumidor

El sistema MUST NOT mostrar al Consumidor la alerta de insumos faltantes / stock insuficiente en el detalle de la solicitud.

### Scenario: Consumidor no ve alerta de faltantes

- **WHEN** un Consumidor abre el detalle y hay stock insuficiente
- **THEN** MUST NOT ver el banner "Insumos Faltantes Detectados"

## Requirement: Campo de observación opcional al despachar

El sistema SHALL mostrar un campo de texto libre (textarea) por cada ítem en el formulario de despacho de Almacén. El campo es opcional. El usuario SHALL poder escribir una observación que se envíe al backend junto con las cantidades.

### Scenario: Almacén escribe observación al despachar

- **WHEN** el usuario de Almacén presiona "Despachar Stock" y escribe una observación en el campo de algún ítem
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `observations: { [detail_id]: "texto" }` en el POST al backend
- **AND** la observación MUST guardarse en `consumption_request_details.observation`

### Scenario: Almacén despacha sin observación

- **WHEN** el usuario de Almacén presiona "Despachar Stock" sin escribir observación en ningún ítem
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `observations: {}` (objeto vacío o sin las keys sin texto)
- **AND** el backend MUST procesar el despacho sin errores

### Scenario: Observación limitada a 500 caracteres

- **WHEN** el usuario de Almacén escribe más de 500 caracteres en el campo de observación
- **THEN** el sistema MUST truncar el texto a 500 caracteres antes de enviarlo

## Requirement: Mostrar observación de despacho en el detalle del ítem

El sistema SHALL mostrar la observación de despacho de cada ítem en el detalle de la solicitud (Show.vue) cuando exista contenido. La observación MUST mostrarse con el patrón visual de "Comentarios de Observación" (texto naranja sobre fondo naranja claro, con borde).

### Scenario: Consumidor ve observación de despacho en desktop

- **WHEN** un usuario abre el detalle de una solicitud
- **AND** un detail tiene `observation` con contenido
- **THEN** MUST ver un bloque con label "Observación de Despacho" y el texto de la observación
- **AND** el bloque MUST usar estilo naranja (text-orange-700, bg-orange-500/5, border-orange-500/10)

### Scenario: No se muestra observación vacía

- **WHEN** un detail tiene `observation` nulo o vacío
- **THEN** el sistema MUST NOT mostrar el bloque de observación para ese ítem

### Scenario: Observación visible en mobile

- **WHEN** un usuario ve el detalle en vista mobile
- **AND** un detail tiene `observation` con contenido
- **THEN** MUST ver el bloque de observación con el mismo estilo que desktop

## Requirement: Mostrar observación en el historial de la solicitud

El sistema SHALL mostrar la observación de despacho del Almacén en la sección de historial/timeline de la solicitud, asociada al evento de despacho.

### Scenario: Observación aparece en timeline

- **WHEN** un usuario ve la sección de historial de una solicitud despachada
- **AND** existe un detail con observation de despacho
- **THEN** MUST ver la observación como parte del entry de despacho en el timeline
- **AND** el texto MUST mostrarse con el mismo patrón visual naranja

### Scenario: Timeline sin observación

- **WHEN** un usuario ve la sección de historial
- **AND** ningún detail tiene observation
- **THEN** el timeline MUST mostrarse sin menciones de observación

## Requirement: Campo de observación opcional en modal de recepción

El sistema SHALL mostrar un campo de texto libre (textarea) dentro del modal SweetAlert de confirmación de recepción. El campo es opcional. El usuario Consumidor SHALL poder escribir una observación que se envíe al backend junto con las cantidades recibidas.

### Scenario: Consumidor escribe observación al recepcionar

- **WHEN** el Consumidor presiona "Confirmar Recepción" y escribe una observación en el textarea del modal
- **AND** confirma la recepción
- **THEN** el sistema MUST enviar `receive_observations: { [detail_id]: "texto" }` en el POST al backend
- **AND** la observación MUST guardarse en `consumption_request_details.receive_observation`

### Scenario: Consumidor recepciona sin observación

- **WHEN** el Consumidor presiona "Confirmar Recepción" sin escribir observación
- **AND** confirma la recepción
- **THEN** el sistema MUST enviar `receive_observations: {}` (objeto vacío)
- **AND** el backend MUST procesar la recepción sin errores

### Scenario: Observación limitada a 500 caracteres

- **WHEN** el Consumidor escribe más de 500 caracteres en el campo de observación
- **THEN** el sistema MUST truncar el texto a 500 caracteres antes de enviarlo

## Requirement: Preservar observación de despacho al recepcionar

El sistema MUST NOT sobreescribir la columna `observation` (observación de despacho de Almacén) al procesar la recepción. La observación del Consumidor MUST almacenarse en una columna separada `receive_observation`.

### Scenario: Observación de despacho se preserva

- **WHEN** un detail tiene `observation` con contenido (observación de despacho de Almacén)
- **AND** el Consumidor confirma la recepción
- **THEN** `detail.observation` MUST permanecer sin cambios
- **AND** la observación del Consumidor MUST guardarse en `detail.receive_observation`

### Scenario: Observación de discrepancia va a receive_observation

- **WHEN** la cantidad recibida difiere de la solicitada
- **AND** el Consumidor ingresa observación obligatoria de discrepancia
- **THEN** la observación MUST guardarse en `receive_observation` (no en `observation`)

## Requirement: Mostrar receive_observation en el timeline

El sistema SHALL mostrar la `receive_observation` de cada ítem en la sección de historial/timeline de la solicitud, asociada al evento de recepción.

### Scenario: Observación de recepción aparece en timeline

- **WHEN** un usuario ve la sección de historial de una solicitud entregada
- **AND** existe un detail con `receive_observation` con contenido
- **THEN** MUST ver la observación como parte del entry de recepción en el timeline
- **AND** el texto MUST mostrarse con patrón visual naranja

### Scenario: Timeline sin receive_observation

- **WHEN** un usuario ve la sección de historial
- **AND** ningún detail tiene `receive_observation`
- **THEN** el timeline MUST mostrarse sin menciones de observación de recepción

## Requirement: Campo de observación opcional en modal de despacho

El sistema SHALL mostrar un campo de texto libre (textarea) dentro del modal SweetAlert de confirmación de despacho. El campo es opcional. El usuario Almacén SHALL poder escribir una observación general que se envíe al backend junto con las cantidades despachadas.

### Scenario: Almacén escribe observación al despachar

- **WHEN** el Almacén presiona "Confirmar Despacho" y escribe una observación en el textarea del modal
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `dispatch_observation: "texto"` en el POST al backend
- **AND** la observación MUST guardarse en `consumption_request.dispatch_observation`

### Scenario: Almacén despacha sin observación

- **WHEN** el Almacén presiona "Confirmar Despacho" sin escribir observación
- **AND** confirma el despacho
- **THEN** el sistema MUST enviar `dispatch_observation: ""` (cadena vacía)
- **AND** el backend MUST procesar el despacho sin errores

### Scenario: Observación limitada a 500 caracteres

- **WHEN** el Almacén escribe más de 500 caracteres en el campo de observación
- **THEN** el sistema MUST truncar el texto a 500 caracteres antes de enviarlo

### Scenario: Observación del modal es independiente de observaciones por ítem

- **WHEN** el Almacén tiene observaciones por ítem en los textareas del formulario
- **AND** escribe una observación en el modal de confirmación
- **THEN** el sistema MUST enviar ambas (`observations` por ítem y `dispatch_observation` del modal)
- **AND** ambas MUST almacenarse en columnas separadas

## Requirement: Show.vue actualiza UI en tiempo real via WebSocket

El componente `ConsumptionRequest/Show.vue` SHALL escuchar eventos WebSocket en el canal `sucursal.{branchId}` y actualizar el estado reactivo `request.value` inmediatamente al recibir un evento `consumption-request.updated`, incluyendo todos los badges, banners, y paneles de acción, sin necesidad de recarga de página.

### Scenario: Consumidor ve transición de pendiente a aprobado en tiempo real

- **WHEN** un usuario con rol Consumidor está visualizando el detalle de una solicitud en estado `pendiente`
- **AND** el Admin aprueba la solicitud desde otra pestaña/dispositivo
- **THEN** el badge MUST cambiar de "Pendiente" a "Aprobado" inmediatamente
- **AND** el banner "Pendiente de Aprobación" MUST ser reemplazado por "En Espera de Despacho"
- **AND** el botón "Cancelar Solicitud" MUST desaparecer (ya no aplica en `aprobado`)

### Scenario: Consumidor ve transición de aprobado a despachado en tiempo real

- **WHEN** un usuario con rol Consumidor está visualizando el detalle de una solicitud en estado `aprobado`
- **AND** Almacén completa el despacho desde otra pestaña/dispositivo
- **THEN** el badge MUST cambiar a "Despachado" o "Despachado Parcial"
- **AND** MUST aparecer la acción "Confirmar Recepción"

### Scenario: Admin ve transición de despachado a entregado en tiempo real

- **WHEN** un usuario con rol Admin está visualizando el detalle de una solicitud en estado `despachado`
- **AND** el Consumidor confirma la recepción desde otra pestaña/dispositivo
- **THEN** el badge MUST cambiar a "Entregado"
- **AND** el panel de acciones MUST actualizarse reflejando el cierre del ciclo

### Scenario: Almacén ve transición en tiempo real

- **WHEN** un usuario con rol Almacén está visualizando el detalle de una solicitud
- **AND** ocurre cualquier transición de estado (aprobación, recepción, cancelación)
- **THEN** el badge, banners y paneles MUST actualizarse inmediatamente
- **AND** MUST recibir un toast informativo según la transición
