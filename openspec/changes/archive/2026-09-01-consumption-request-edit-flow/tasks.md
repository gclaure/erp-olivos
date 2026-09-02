## 1. Backend Service & Controller Implementation

- [x] 1.1 Agregar método `updateRequest(ConsumptionRequest $consumptionRequest, array $data, array $items)` en `ConsumptionRequestService.php` con validación estricta de estado pendiente, no aprobación y pertenencia de autor
- [x] 1.2 Agregar rutas `GET /admin/consumption-requests/{consumption_request}/edit` y `PUT /admin/consumption-requests/{consumption_request}` en `routes/admin.php`
- [x] 1.3 Implementar métodos `edit` y `update` en `ConsumptionRequestController.php`
- [x] 1.4 Agregar campo computado `can_edit` en `ConsumptionRequestResource.php`

## 2. Frontend Integration

- [x] 2.1 Agregar botón `[ ✏️ Editar Solicitud ]` en la vista de detalle `resources/js/Pages/Admin/ConsumptionRequest/Show.vue` condicionado por `can_edit`
- [x] 2.2 Adaptar `resources/js/Pages/Admin/POS/Index.vue` para recibir `editingRequest`, precargar el carrito con los productos existentes y enviar `PUT` a la ruta de actualización

## 3. Verification & Testing

- [x] 3.1 Compilar assets con `npm run build`
- [x] 3.2 Verificar el flujo completo: creación, edición de cantidades, adición de nuevos productos, eliminación de ítems y bloqueo tras aprobación
