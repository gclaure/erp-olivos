## Context

El flujo de despacho de solicitudes de consumo tiene un gap: el frontend recolecta observaciones (`dispatchObservations` ref con textarea y dictado por voz) pero `handleDispatch()` solo envía `{ quantities: payload }` — nunca incluye `observations`. El backend ya valida `observations` como opcional y el `ConsumptionRequestDispatchService` ya la procesa (la guarda en `consumption_request_details.observation` cuando hay over-dispatch). La columna `observation` existe en la tabla pero nunca se muestra en la UI.

**Stack relevante:**
- Frontend: Vue 3 + Inertia.js + Tailwind CSS
- Backend: Laravel 12, `ConsumptionRequestController::dispatchRequest()`
- Vista: `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`
- Servicio: `app/Services/ConsumptionRequestDispatchService.php`
- DB: `consumption_request_details.observation` ya existe

## Goals / Non-Goals

**Goals:**
- Permitir al usuario de Almacén escribir una observación libre por ítem al despachar
- Enviar la observación al backend (fix del bug existente)
- Mostrar la observación en el detalle de cada ítem (Show.vue) con el patrón visual de "Comentarios de Observación"
- Mostrar la observación en el historial/timeline de la solicitud
- Funcional para desktop y mobile

**Non-Goals:**
- Crear tabla de historial de estados (no se pide)
- Agregar observación obligatoria en estados que no son despacho
- Modificar el backend (ya está listo para recibir observations)
- Agregar notificaciones nuevas

## Decisions

### 1. textarea inline por ítem vs. modal de despacho

**Decisión:** Textarea inline por ítem dentro de la tabla/formulario de despacho.

**Alternativas consideradas:**
- Modal de despacho con observación global: Rechazado — la observación es por producto, no por solicitud completa.
- Textarea siempre visible: Rechazado — solo se muestra cuando el usuario interactúa con la fila o cuando la observation ya existe.

**Razón:** Un textarea colapsado por fila que se expande al hacer click en "Agregar observación" mantiene la UI limpia pero accesible.

### 2. Envío de observations en el payload

**Decisión:** Incluir `observations: { [detail_id]: "texto" }` en el POST junto a `quantities`.

**Razón:** El backend ya valida `observations` como array de strings nullable. Solo falta enviarlo. No se requiere cambio backend.

### 3. Visualización de observation existente

**Decisión:** Mostrar observation con el patrón "Comentarios de Observación" (badge naranja) debajo de cada item detail card, tanto en desktop como mobile.

**Razón:** Consistencia visual con el patrón ya existente en el proyecto. El observation se muestra solo cuando tiene contenido (no vacío).

### 4. Observation en timeline/historial

**Decisión:** Si el detail tiene observation, mostrarla como entry adicional en la sección de historial/timeline de la solicitud, vinculada al evento de despacho.

**Razón:** El usuario pidió que la observación "esté en el historial". Como no hay tabla de historial, se muestra inline en la sección de timeline existente del Show.vue.

## Risks / Trade-offs

- **[Riesgo]** El usuario podría escribir observaciones muy largas → **Mitigación:** limitar a 500 caracteres en frontend, trim en backend.
- **[Riesgo]** Observaciones vacías se envían al backend → **Mitigación:** filtrar en frontend antes de enviar (no incluir keys con string vacío).
- **[Trade-off]** No se crea audit trail completo de despachos anteriores → aceptado por alcance del change.
