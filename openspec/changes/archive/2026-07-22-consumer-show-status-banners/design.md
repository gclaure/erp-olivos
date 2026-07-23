## Context

`Show.vue` tiene banners y un panel de acciones compartido. El título “Acciones de Almacén” se renderiza siempre. El banner de espera de despacho se muestra en `pendiente` para Consumidor, lo cual contradice el flujo Aprobar → Despachar.

Decisiones del usuario:
- Copy lo define el implementador según el estado.
- Cancelar Consumidor: solo `pendiente`, no `aprobado`.
- Ocultar alerta de insumos faltantes al Consumidor: sí.

## Goals / Non-Goals

**Goals:**

- Mensajes de estado correctos para Consumidor en `pendiente` y `aprobado`.
- Panel de acciones sin jerga de Almacén para Consumidor.
- Cancelar solo antes de aprobación.
- Ocultar alerta operativa de faltantes al Consumidor.

**Non-Goals:**

- No tocar backend, notificaciones, ni create.
- No reescribir banners de Almacén/Admin en este change (salvo que el panel compartido lo exija).
- No cambiar lógica de Confirmar Recepción.

## Decisions

### 1. Banners Consumidor

| Status | Título | Cuerpo |
|--------|--------|--------|
| `pendiente` | Pendiente de Aprobación | Su solicitud fue registrada exitosamente. Está a la espera de la aprobación del Administrador. |
| `aprobado` | En Espera de Despacho | Su solicitud fue aprobada. El almacenero está preparando los insumos para el despacho. |

Implementación: dos bloques `v-if` (o uno con copy dinámico). Preferir dos bloques para claridad.

### 2. Panel de acciones por rol

- Título “Acciones de Almacén”: `v-if="!isConsumidorRole"` (o solo cuando hay acciones de almacén visibles).
- Para Consumidor con acciones propias: título “Mis Acciones” cuando exista al menos una acción (Cancelar o Recepcionar).
- Evitar panel vacío: el contenedor del panel debe mostrarse solo si hay contenido relevante para el rol actual.

### 3. Cancelar

```js
// Consumidor
v-if="isConsumidorRole && request.status === 'pendiente'"

// Otros roles (mantener comportamiento actual o el ya existente)
v-if="!isConsumidorRole && (pendiente || aprobado || observado)"
```

Backend de cancel no se cambia en este change (solo UI Consumidor). Si se desea endurecer backend, otro change.

### 4. Alerta insumos faltantes

Añadir `&& !isConsumidorRole` al `v-if` del banner de faltantes.

### 5. Confirmar Recepción

Sin cambios de condición: `isConsumidorRole && (despachado || despachado_parcial)`.

## Risks / Trade-offs

- **[Risk]** Admin/Almacén pierden Cancelar si se rompe el `v-if` compartido → **Mitigation:** ramas explícitas por rol.
- **[Risk]** Panel vacío residual → **Mitigation:** condicionar el wrapper del panel a acciones visibles.
