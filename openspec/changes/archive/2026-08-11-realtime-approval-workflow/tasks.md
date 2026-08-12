## 1. Backend - Agregar campo action al evento

- [x] 1.1 Modificar `ConsumptionRequestUpdated` para aceptar un parámetro `action` (string) en el constructor
- [x] 1.2 Actualizar `broadcastWith()` para incluir el campo `action` en el payload del evento
- [x] 1.3 Actualizar todas las llamadas a `event(new ConsumptionRequestUpdated(...))` en `ConsumptionRequestController` para pasar el parámetro `action` correcto: `approved` en approve(), `observed` en observe(), `received` en receive(), `dispatched` en dispatchRequest(), `cancelled` en cancel()

## 2. Frontend - Show.vue listener con toasts diferenciados

- [x] 2.1 Modificar el listener `.consumption-request.updated` en `Show.vue` para extraer `e.action` del evento
- [x] 2.2 Implementar lógica de toast diferenciado por `action` y rol del usuario: toast "Solicitud Aprobada" para admin en `approved`, toast "Solicitud Despachada" para consumidor en `dispatched`, toast "Recepción Confirmada" para admin/almacén en `received`
- [x] 2.3 Agregar guard para evitar toast duplicado cuando el usuario que ejecuta la acción está en la misma página (verificar que el evento viene de otro usuario comparando con `page.props.auth.user.id`)
- [x] 2.4 Mantener el toast existente de Almacén para `entregado` (recepción) con el mensaje detallado ya implementado

## 3. Frontend - Actualización reactiva de UI

- [x] 3.1 Verificar que `request.value = e.request` actualiza correctamente badges, banners y paneles de acción (el template ya usa `request.value.status` en condiciones `v-if`)
- [x] 3.2 Agregar toast para consumidor cuando recibe evento `action: "dispatched"` indicando que el almacén despachó los insumos
- [x] 3.3 Agregar toast para admin cuando recibe evento `action: "received"` indicando que el consumidor recibió los insumos

## 4. Verificación y pruebas

- [x] 4.1 Verificar que el evento `ConsumptionRequestUpdated` con `action` funciona correctamente al aprobar (admin aprueba → Show.vue de consumidor actualiza badge y banner)
- [x] 4.2 Verificar que el evento funciona al despachar (almacén despacha → Show.vue de consumidor muestra toast y panel de recepción)
- [x] 4.3 Verificar que el evento funciona al recepcionar (consumidor recibe → Show.vue de admin muestra toast y badge "Entregado")
- [x] 4.4 Verificar que la campana de notificaciones se actualiza cuando consumidor recibe (evento `NuevaNotificacion` ya despachado en receive())
- [x] 4.5 Verificar que no hay errores de TypeScript/compilación en Show.vue
