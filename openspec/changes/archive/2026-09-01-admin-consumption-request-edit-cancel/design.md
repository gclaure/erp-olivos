## Context

En el flujo de solicitudes de consumo interno (`ConsumptionRequest`), el Administrador es la autoridad que valida la viabilidad y necesidad de los insumos solicitados antes de que Almacén prepare o despache el pedido. El Administrador necesita poder ajustar las cantidades solicitadas de los productos o cancelar la solicitud si no procede, informando inmediatamente al área solicitante y a almacén.

## Goals / Non-Goals

**Goals:**
- Proporcionar un endpoint y control en UI para que el Administrador modifique la cantidad solicitada (`quantity_requested`) de un ítem en estado `pendiente` u `observado`.
- Restringir la cancelación de solicitudes exclusivamente al rol Administrador.
- Enviar notificaciones (`database` y `NuevaNotificacion` en tiempo real vía WebSocket) al usuario solicitante (`$request->user`) y al personal de almacén de la sucursal ante cancelaciones o modificaciones de cantidad.
- Disparar el evento `ConsumptionRequestUpdated` en el canal de sucursal con las acciones `'cancelled'` y `'item_updated'` para sincronizar las vistas abiertas reactivamente.

**Non-Goals:**
- Permitir la edición de cantidades una vez que la solicitud haya sido despachada o entregada.
- Modificar el flujo de recepción del consumidor ni el flujo de despacho de almacén.

## Decisions

### 1. Endpoint y Método para Actualizar Cantidad de Ítem
- Se agrega la ruta `PUT /admin/consumption-requests/{consumption_request}/details/{detail}` controlada por `ConsumptionRequestController@updateDetailQuantity`.
- Validación:
  - Usuario debe ser Administrador (`isAdmin`).
  - La solicitud debe estar en estado `pendiente` u `observado`.
  - `quantity_requested`: numérico obligatorio > 0.
  - Opcional: `modification_notes` (motivo del cambio).
- En `ConsumptionRequestService`, se agrega el método `updateDetailQuantity(ConsumptionRequest $request, ConsumptionRequestDetail $detail, float $newQty, ?string $notes)`.

### 2. Restricción y Notificación en Cancelación
- En `ConsumptionRequestController@cancel`:
  - Validar que el usuario autenticado sea Administrador (`isAdmin`).
  - Validar estado modificable (`pendiente`, `observado`, `aprobado`).
  - Ejecutar `cancelRequest` en el servicio.
  - Obtener destinatarios: `$consumptionRequest->user` (creador) y usuarios con rol `Almacén` de la misma sucursal.
  - Enviar `SolicitudConsumoCanceladaNotification` a cada destinatario y disparar `NuevaNotificacion::dispatch`.
  - Disparar `event(new ConsumptionRequestUpdated($consumptionRequest, 'cancelled'))`.

### 3. Clases de Notificación
- `SolicitudConsumoCanceladaNotification`: Informa que la solicitud #X fue cancelada por el administrador y adjunta el motivo.
- `SolicitudConsumoModificadaNotification`: Informa que el administrador modificó la cantidad solicitada del producto X a Y unidades.

### 4. UI en `Show.vue`
- En la tabla de productos (versión desktop y tarjetas mobile):
  - Si `isAdmin` es true y `request.status` está en `['pendiente', 'observado']`, se agrega un botón de edición (ícono de lápiz) junto a la cantidad solicitada.
  - Al hacer clic, se abre un diálogo interactivo (SweetAlert2) con input numérico y nota opcional para confirmar el cambio.
- En el panel de acciones lateral:
  - El botón "Cancelar Solicitud" se muestra exclusivamente para el Administrador (`isAdmin && ['pendiente', 'observado', 'aprobado'].includes(request.status)`). Se retira la condición que lo limitaba a consumidor.

## Risks / Trade-offs

- **[Riesgo] Notificar a usuarios de almacén de otra sucursal** → *Mitigación:* Filtrar estrictamente `User::where('branch_id', $consumptionRequest->warehouse->branch_id)->whereHas('roles', fn($q) => $q->whereIn('name', ['Almacén', 'almacen', 'Almacen', 'almacén']))`.
- **[Riesgo] Discrepancia si se intenta editar una solicitud ya despachada** → *Mitigación:* Validación estricta a nivel de base de datos / backend que arroja excepción 422/403 si el estado no es `pendiente` u `observado`.
