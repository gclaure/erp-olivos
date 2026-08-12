## Context

El sistema de solicitudes de consumo ya tiene infraestructura de tiempo real completa:
- **Backend**: `ConsumptionRequestUpdated` se despacha en approve(), observe(), receive() y dispatchRequest()
- **Frontend**: `Show.vue` escucha `.consumption-request.updated` en el canal `sucursal.{branchId}` y actualiza `request.value`
- **Notificaciones**: `NuevaNotificacion` se despacha para bell de notificaciones (campana)

Sin embargo, la experiencia de usuario es incompleta:
- Show.vue solo muestra toast cuando Almacén ve status `entregado` (línea 91)
- El admin que está en la misma página no ve feedback cuando el consumidor recibe
- No hay toast informativo cuando el admin aprueba y otro admin está viendo la misma solicitud
- El consumidor no ve feedback inmediato cuando almacén despacha (solo ve el cambio de badge)

## Goals / Non-Goals

**Goals:**
- Show.vue MUST reaccionar a cada transición de estado con feedback visual específico por rol
- Cuando el Consumidor recibe, el Admin en la misma página MUST ver un toast de notificación en tiempo real
- Cada transición (aprobar, despachar, recepcionar) MUST tener un toast diferenciado
- Mantener el evento `ConsumptionRequestUpdated` genérico (no crear eventos separados)

**Non-Goals:**
- No se modifica el evento `ConsumptionRequestUpdated` existente (se mantiene backward compatible)
- No se crea infraestructura nueva de WebSocket (ya existe y funciona)
- No se incluyen Purchase Orders en este cambio
- No se modifica el Index.vue (ya funciona correctamente con el evento genérico)

## Decisions

### 1. Agregar campo `action` al payload del evento

**Decisión**: Extender `ConsumptionRequestUpdated` para incluir un campo `action` en `broadcastWith()` que identifique la transición (`approved`, `dispatched`, `received`, `observed`, `cancelled`).

**Alternativa considerada**: Crear eventos separados (`ConsumptionRequestApproved`, etc.). Rechazado porque:
- Duplica código (cada evento es casi idéntico)
- Rompe compatibilidad con listeners existentes
- Complejiza el frontend con múltiples `.listen()`

**Razón**: Un solo evento con `action` permite al frontend decidir qué toast mostrar sin cambiar la arquitectura de canales.

### 2. Lógica de toast en Show.vue por rol y acción

**Decisión**: En el listener `.consumption-request.updated`, usar `e.action` + el rol del usuario para determinar qué toast mostrar:

| Acción | Rol Consumidor | Rol Almacén | Rol Admin |
|--------|---------------|-------------|-----------|
| `approved` | Toast "Solicitud aprobada" | — | Toast "Solicitud aprobada" |
| `dispatched` | Toast "Solicitud despachada" | Toast "Despacho registrado" | — |
| `received` | Toast "Recepción confirmada" | Toast "Recepción confirmada" | Toast "Consumidor recibió solicitud" |
| `observed` | Toast "Solicitud observada" | — | Toast "Solicitud observada" |

**Razón**: Cada rol necesita saber qué pasó, pero no todos necesitan el mismo nivel de detalle.

### 3. Notificación push al admin en recepción

**Decisión**: El evento `NuevaNotificacion` ya se despacha en `receive()` a los admin users (líneas 426-434 del controller). No necesita cambios en backend. El frontend ya tiene `NotificationBell.vue` que escucha ese canal. Solo se necesita agregar toast específico en Show.vue para admins que estén en la página de la misma solicitud.

## Risks / Trade-offs

- **Riesgo**: Agregar `action` al payload puede romper listeners existentes que no esperan ese campo → **Mitigación**: El campo es adicional, no reemplaza `request`. Listeners existentes simplemente lo ignoran.
- **Riesgo**: Múltiples toasts simultáneos si varios usuarios actúan → **Mitigación**: Los toasts ya usan `timer: 6000` y `position: top-end`, son no intrusivos.
- **Trade-off**: No se crean eventos específicos por transición → A cambio se mantiene un solo evento y un solo canal, simplificando el sistema.
