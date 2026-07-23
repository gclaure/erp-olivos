## Context

En `ConsumptionRequestController@store`, tras crear cada solicitud se notifica solo a usuarios con rol Almacén de la sucursal del almacén:

```php
User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Almacén', ...]))
    ->where('branch_id', $consumptionRequest->warehouse->branch_id)
    ->where('is_active', true)
```

Admin y super admin quedan fuera. Otros módulos (stock bajo, compras) usan un criterio más amplio: super admin **o** misma sucursal, a veces excluyendo Consumidor.

La campana ya soporta `type: new_consumption_request`; no requiere cambios de frontend si el registro llega a la tabla `notifications` del usuario correcto.

## Goals / Non-Goals

**Goals:**

- Admin / Administrador de la sucursal del almacén reciben BD + socket al crear solicitud.
- Super admin recibe siempre la notificación de nueva solicitud.
- Almacén de la sucursal sigue recibiendo (sin regresión).
- Consumidor no recibe esta notificación.
- Solo usuarios activos.

**Non-Goals:**

- Cambiar texto/tipo de `NuevaSolicitudConsumoNotification`.
- Ampliar destinatarios de despacho/recepción/observación en este change.
- Refactor global a un servicio de notificaciones compartido (opcional futuro).
- Cambiar canales de broadcast de listado (`ConsumptionRequestCreated`).

## Decisions

### 1. Criterio de destinatarios (alineado a StockObserver + roles operativos)

Incluir usuario si está activo y cumple **cualquiera**:

1. `is_super_admin = true`, **o**
2. `branch_id` = sucursal del almacén de la solicitud **y** tiene rol en:
   - Almacén / almacen / Almacen / almacén
   - Admin / admin / Administrador / administrador

**Y** no tiene solo rol Consumidor de forma que deba excluirse: aplicar `whereDoesntHave` roles Consumidor/consumidor para no notificar a consumidores aunque por error compartan branch.

**Alternativa rechazada A**: Notificar a todos los de la sucursal excepto Consumidor (como stock). Más ruido para roles no operativos de consumo.

**Alternativa rechazada B**: Solo añadir Admin a la query actual sin super admin. Super admin multi-sucursal no vería alertas.

### 2. Dónde implementar

- **Decisión**: Ajustar la query inline en `store` (mismo sitio que hoy).
- **Alternativa**: Extraer método privado `recipientsForNewConsumptionRequest(ConsumptionRequest $cr): Collection` en el controller o un helper — preferible si el bloque crece; para este fix, método privado en el controller es suficiente y testeable.

### 3. Deduplicación

- Usar `->unique('id')` por si un usuario tiene Admin + Almacén.
- No notificar dos veces al mismo user.

### 4. Excluir al creador

- **Decisión**: No excluir al creador en v1. Si un Admin crea la solicitud, también se notifica a sí mismo (aceptable; puede marcar como leída). Si molesta, se filtra `where('id', '!=', auth()->id())` en un follow-up.

### 5. Entrega

- Por cada destinatario: `$user->notify(new NuevaSolicitudConsumoNotification(...))` + `NuevaNotificacion::dispatch` en try/catch (igual que hoy).

## Risks / Trade-offs

- **[Risk] Admin sin `branch_id`** → Mitigation: super admin cubre multi-tenant; Admin de sucursal debe tener `branch_id` (modelo actual de usuarios).
- **[Risk] Variantes de nombre de rol** → Mitigation: incluir las mismas variantes que ya usa el controller (`Admin`, `admin`, `Administrador`, `administrador`).
- **[Trade-off] No unificar con receive/dispatch** → Scope acotado; mismo bug puede existir allí pero no se toca ahora.

## Migration Plan

1. Deploy del cambio de query en `store`.
2. Sin migraciones.
3. Rollback: revertir commit.

## Open Questions

- Ninguna bloqueante. Exclusión del creador queda como mejora opcional.
