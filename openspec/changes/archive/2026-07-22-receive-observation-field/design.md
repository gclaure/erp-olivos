## Context

El flujo de recepción de solicitudes de consumo usa un SweetAlert modal de confirmación sin campo de observación. La observación del Consumidor se envía solo cuando hay discrepancia de cantidades, y el backend sobreescribe la columna `observation` compartida con el despacho. Esto significa que la observación que dejó Almacén al despachar se pierde al recepcionar.

**Stack relevante:**
- Frontend: Vue 3 + Inertia.js + SweetAlert2
- Backend: Laravel 12, `ConsumptionRequestService::receiveRequest()`
- DB: `consumption_request_details.observation` (compartido dispatch/receive)

## Goals / Non-Goals

**Goals:**
- Agregar textarea opcional en el modal SweetAlert de confirmación de recepción
- Preservar la observación de despacho de Almacén (no sobreescribir)
- Nuevo campo `receive_observation` en la tabla para guardar la observación del Consumidor
- Mostrar `receive_observation` en el timeline con patrón visual distinto

**Non-Goals:**
- Modificar el comportamiento de observación obligatoria por discrepancia (ya existe)
- Crear tabla de historial
- Agregar notificaciones nuevas

## Decisions

### 1. Nuevo campo `receive_observation` vs. sobreescribir `observation`

**Decisión:** Nuevo campo `receive_observation` nullable en `consumption_request_details`.

**Alternativas consideradas:**
- Sobreescribir `observation`: Rechazado — perdería la observación de despacho de Almacén.
- Concatenar en `observation`: Rechazado — dificulta distinguir quién escribió qué.

**Razón:** Separar dispatch y receive observations mantiene la integridad de cada una. El campo existente `observation` sigue siendo la observación de Almacén al despachar.

### 2. Textarea en SweetAlert modal vs. textarea inline previo

**Decisión:** Usar SweetAlert2 con `html` option que incluya un textarea custom.

**Razón:** El usuario pidió que el textarea aparezca dentro del modal de confirmación. SweetAlert2 soporta HTML custom con inputs.

### 3. Envío de `receive_observations` al backend

**Decisión:** Incluir `receive_observations: { [detail_id]: "texto" }` en el POST junto a `received_quantities`.

**Razón:** Consistente con el patrón de dispatch. El backend recibe el array y lo guarda en `receive_observation`.

### 4. Backend: guardar en `receive_observation`

**Decisión:** Modificar `receiveRequest()` para guardar `$observations` en `detail->receive_observation` en vez de `detail->observation`.

**Razón:** Preserva la observación de despacho. La observación de discrepancia obligatoria ahora va a `receive_observation` también.

## Risks / Trade-offs

- **[Riesgo]** SweetAlert textarea puede no ser tan estilizado como un textarea inline → **Mitigación:** usar `html` con clases de Tailwind para mantener consistencia visual.
- **[Riesgo]** Datos existentes no tienen `receive_observation` → **Mitigación:** campo nullable, defaults a null.
- **[Trade-off]** Una migración adicional → aceptado por la necesidad de separar observaciones.
