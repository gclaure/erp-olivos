# Design

## Context

Actualmente en el sistema, eventos como el registro de compras (`PurchaseService`), discrepancias en recepciones de transferencias (`TransferService`) y ajustes de stock (`MovementService`) obtienen sus destinatarios filtrando usuarios por `branch_id` o `is_super_admin`, sin discriminar por roles:
```php
$users = User::where('is_super_admin', true)
    ->orWhere('branch_id', $branchId)
    ->get();
```
Esto provoca que los usuarios con rol `Consumidor` vinculados a una sucursal reciban en su base de datos y campana notificaciones que no corresponden a su labor. Además, en `ConsumptionRequest/Index.vue`, la suscripción a `sucursal.{branchId}` dispara alertas nativas y toasts al recibir `.consumption-request.created`, alertando a los consumidores sobre solicitudes ajenas.

## Goals / Non-Goals

**Goals:**
- Excluir de manera uniforme y consistente a los usuarios con rol `Consumidor` / `consumidor` de todas las notificaciones operativas generales (compras, movimientos y transferencias).
- Garantizar que al Consumidor le lleguen exclusivamente notificaciones de **sus propias solicitudes** (`SolicitudConsumoAprobadaNotification`, `SolicitudConsumoDespachadaNotification`, `SolicitudConsumoCanceladaNotification` y `SolicitudConsumoModificadaNotification`).
- En el frontend (`Index.vue`), silenciar toasts y sonidos de navegador de `.consumption-request.created` cuando el usuario autenticado sea Consumidor.
- En `NotificationBell.vue`, dar soporte completo a las notificaciones de cancelación y modificación de solicitudes con enlaces e iconos adecuados.

**Non-Goals:**
- No alterar las notificaciones que reciben los Administradores, Super Administradores o personal de Almacén.
- No alterar el canal privado WebSocket `notificaciones.{userId}` para otros tipos de usuarios.

## Decisions

### 1. Scope/Filtro Eloquent reutilizable para exclusión de consumidores
- **Decisión:** En `PurchaseService`, `MovementService` y `TransferService`, encadenar:
  ```php
  ->whereDoesntHave('roles', function ($query) {
      $query->whereIn('name', ['Consumidor', 'consumidor']);
  })
  ```
- **Alternativas consideradas:**
  - *Filtrar en memoria con colección de Laravel:* Se descartó porque enviar queries filtradas directamente a PostgreSQL ahorra memoria y evita hidratar modelos innecesarios.
  - *Cambiar la definición de roles a permisos granulares:* Innecesario y riesgoso en este momento dado que el sistema ya utiliza consistentemente los nombres de roles para control de acceso.

### 2. Filtrado de eventos en tiempo real en frontend (`Index.vue`)
- **Decisión:** Al recibir `.consumption-request.created`, antes de disparar el toast de Swal y `mostrarNotificacionBrowser()`, verificar si el usuario tiene rol Consumidor:
  ```javascript
  if (!isConsumidor.value) {
      // Toast en pantalla y notificación nativa del navegador con sonido
  }
  ```
  La fila se sigue agregando a la tabla local si corresponde, pero se silencia la notificación invasiva y el sonido.

### 3. Sintonización de tipos en `NotificationBell.vue`
- **Decisión:** Agregar los casos `consumption_request_cancelled` y `consumption_request_modified` al switch de estilos e iconos de la campana, y mapear `urlDestino` a `route('admin.consumption-requests.index')`.

## Risks / Trade-offs

- **[Riesgo]** Un usuario que tenga simultáneamente rol Consumidor y otro rol (ej. Admin o Almacén) en entornos de prueba podría no recibir notificaciones operativas.
  - **Mitigación:** En la arquitectura actual del sistema, la regla de negocio define que un usuario asignado a un Área es estrictamente un Consumidor (`UserController.php`). Para administradores y almaceneros, sus roles son exclusivos y no tienen asignado el rol Consumidor.
