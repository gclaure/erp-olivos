## Context

Hoy el ciclo de consumo es inconsistente con el negocio:

- `ConsumptionRequestDispatchService` acepta despacho desde `pendiente`, `aprobado` y `despachado_parcial`.
- `approve` solo emite `ConsumptionRequestUpdated` (sin notificación al creador).
- `dispatchRequest` ya notifica a consumidores del área (`SolicitudConsumoDespachadaNotification`).
- `receive` notifica a usuarios Almacén de la sucursal; el negocio pide notificar al Administrador.

La creación (`store`) y el catálogo de creación no se tocan.

## Goals / Non-Goals

**Goals:**

- Aprobación del Administrador como paso obligatorio antes del primer despacho.
- Notificar al Consumidor creador al aprobar y al despachar.
- Notificar solo a Administradores (sucursal + super admin según patrón existente) al recepcionar.
- Alinear UI de `Show.vue` con esos estados y roles.

**Non-Goals:**

- No modificar creación de solicitudes.
- No implementar cancelación, edición de ítems, ni tabla de historial de estados en este change.
- No cambiar el rol que despacha (sigue siendo solo Almacén).
- No rediseñar Index.vue más allá de lo que ya se actualiza por socket.

## Decisions

### 1. Estados permitidos para despacho

- **Antes:** `pendiente | aprobado | despachado_parcial`
- **Después:** `aprobado | despachado_parcial`
- Razón: el primer despacho exige aprobación; `despachado_parcial` permite completar despachos ya iniciados sin re-aprobar.
- Frontend: `canUserDispatch` / botón "Despachar Stock" solo con esos estados (ya filtrado por rol Almacén).

### 2. Destinatario de notificación al aprobar

- Notificar a `consumptionRequest.user` (creador), si está activo.
- Clase nueva: `SolicitudConsumoAprobadaNotification` (canal `database`) + `NuevaNotificacion` tipo `consumption_request_approved`.
- Patrón idéntico a despacho/recepción existentes.

### 3. Destinatario al despachar

- Mantener notificación al Consumidor; preferir el **creador** (`user_id`) si es más preciso que “todos los consumidores del área”.
- Decisión: notificar al **creador** de la solicitud (activo). Si no hay creador activo, no fallar el despacho.
- Reutilizar o ajustar `SolicitudConsumoDespachadaNotification` y tipo `consumption_request_dispatched`.

### 4. Destinatario al recepcionar

- **Dejar de** notificar a Almacén.
- Notificar a usuarios activos con rol Admin/Administrador de la misma `branch_id` del almacén de la solicitud, más super admins (mismo criterio de deduplicación que en creación).
- Reutilizar/ajustar `SolicitudConsumoRecepcionadaNotification` y tipo `consumption_request_received`.

### 5. UI Show.vue

- Sección Admin: Aprobar solo en `pendiente` / `observado` (sin cambio de rol).
- Despachar: solo Almacén y status `aprobado` | `despachado_parcial`.
- Recepcionar: solo Consumidor y status `despachado` | `despachado_parcial` (sin cambio de regla de recepción).
- Ocultar o no mostrar acciones de almacén irrelevantes al Consumidor (ya parcialmente hecho).

### 6. Fuera de alcance consciente

Cancelar/editar/trazabilidad se posponen; este change solo cierra el happy path Aprobar → Despachar → Recepcionar + notificaciones.

## Risks / Trade-offs

- **[Risk]** Solicitudes ya en `pendiente` con stock listo no se pueden despachar hasta aprobación → **Mitigation:** comportamiento deseado; Admin debe aprobar.
- **[Risk]** Despacho parcial previo sin aprobación histórica no aplica a datos nuevos; datos viejos en `pendiente` quedan bloqueados hasta approve → **Mitigation:** aceptable; Admin aprueba y Almacén despacha.
- **[Risk]** Cambiar destinatarios de recepción puede sorprender a Almacén → **Mitigation:** requisito explícito del negocio; Almacén sigue viendo el listado por socket.

## Migration Plan

- Deploy código (sin migración de BD).
- No se requiere backfill.
- Rollback: revertir commits de controller/service/vue/notifications.
