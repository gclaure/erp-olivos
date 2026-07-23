## Why

El flujo post-creación de solicitudes de consumo no respeta el negocio: Almacén puede despachar sin aprobación del Administrador, la aprobación no notifica al Consumidor, y la recepción notifica a Almacén en lugar del Administrador. Hay que alinear aprobación, despacho, recepción y notificaciones sin tocar la creación.

## What Changes

- **BREAKING (regla de negocio):** el despacho solo es posible cuando la solicitud está en estado `aprobado` (o `despachado_parcial` para completar un despacho ya iniciado). Ya no se despacha desde `pendiente`.
- Al **aprobar**, el sistema notifica al Consumidor creador (BD + campana).
- Al **despachar**, se mantiene/ajusta la notificación al Consumidor indicando que puede recepcionar.
- Al **recepcionar**, la notificación va al **Administrador** de la sucursal (no a Almacén).
- UI en `Show.vue`: botones y condiciones de estado coherentes con el flujo (Aprobar → Despachar solo si aprobado → Recepcionar).
- **No se modifica** la creación de solicitudes (`store` / create).

## Capabilities

### New Capabilities

- `consumption-request-lifecycle`: flujo post-creación (aprobación obligatoria, despacho condicionado, recepción y cierre del ciclo del Consumidor)

### Modified Capabilities

- `consumption-request-notifications`: destinos de notificación en aprobar, despachar y recepcionar

## Impact

- `app/Services/ConsumptionRequestDispatchService.php` — estados permitidos para despacho
- `app/Http/Controllers/Admin/ConsumptionRequestController.php` — `approve`, `dispatchRequest`, `receive` (notificaciones)
- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` — visibilidad de acciones por rol/estado
- Nuevas o ajustadas clases `App\Notifications\*` para aprobación y recepción
- Specs: `openspec/specs/consumption-request-notifications`, nuevo `consumption-request-lifecycle`
