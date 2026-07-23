## 1. Destinatarios al crear solicitud

- [x] 1.1 En `ConsumptionRequestController@store`, reemplazar la query que solo filtra rol Almacén por una que incluya: super admin **o** (misma `branch_id` de la sucursal del almacén **y** rol Almacén o Admin/Administrador), usuarios activos, excluyendo rol Consumidor
- [x] 1.2 Aplicar `unique('id')` sobre la colección de destinatarios antes del foreach de notify/dispatch
- [x] 1.3 (Opcional pero recomendado) Extraer la resolución de destinatarios a un método privado reutilizable en el controller, p. ej. `recipientsForNewConsumptionRequest(ConsumptionRequest $request)`

## 2. Entrega de notificación

- [x] 2.1 Conservar por destinatario: `notify(NuevaSolicitudConsumoNotification)` + `NuevaNotificacion::dispatch` en try/catch (sin cambiar mensaje ni tipo)
- [x] 2.2 Verificar que no se altera el flujo de creación ni el redirect de éxito

## 3. Verificación

- [ ] 3.1 Crear solicitud como Consumidor y confirmar que un Admin de la sucursal recibe la notificación en campana/BD
- [ ] 3.2 Confirmar que un usuario Almacén de la misma sucursal sigue recibiendo
- [ ] 3.3 Confirmar que un Consumidor no recibe la notificación de nueva solicitud
- [ ] 3.4 Confirmar que super admin recibe la notificación
