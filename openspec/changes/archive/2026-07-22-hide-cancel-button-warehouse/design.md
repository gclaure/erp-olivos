## Context

En `Show.vue:1665` el botón "Cancelar Solicitud" tiene esta condición:

```html
v-if="(isConsumidorRole && request.status === 'pendiente') || (!isConsumidorRole && (request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'observado'))"
```

Esto muestra el botón a usuarios Almacén y Admin cuando la solicitud está en `pendiente`, `aprobado` u `observado`. Según las reglas de negocio, solo el Consumidor que creó la solicitud puede cancelarla.

## Goals / Non-Goals

**Goals:**
- Ocultar el botón "Cancelar Solicitud" para todos los roles que no sean Consumidor
- Mantener el comportamiento actual del Consumidor (solo cancelar en `pendiente`)

**Non-Goals:**
- Modificar el backend para bloquear cancelaciones por rol (ya existe validación en el controlador)
- Cambiar el comportamiento de otros botones de acción

## Decisions

**Decisión 1: Cambiar la condición `v-if` a solo `isConsumidorRole`**

La condición actual tiene dos ramas: una para Consumidor y otra para `!isConsumidorRole`. La rama de `!isConsumidorRole` se elimina completamente.

Nueva condición:
```html
v-if="isConsumidorRole && request.status === 'pendiente'"
```

**Alternativa descartada**: Mantener la condición y agregar un check de `isConsumidorRole` en la rama `!isConsumidorRole` — redundante ya que `!isConsumidorRole` implica que no es Consumidor.

## Risks / Trade-offs

**Riesgo**: Un usuario Almacén que tenía permiso tácito para cancelar solicitudes pendientes pierde esa capacidad.
**Mitigación**: La cancelación es responsabilidad del Consumidor (quien creó la solicitud). El controlador ya valida que solo el creador pueda cancelar.
