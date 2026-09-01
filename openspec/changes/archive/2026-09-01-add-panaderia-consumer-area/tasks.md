## 1. Backend Updates

- [x] 1.1 Actualizar las reglas de validación de `area` en `UserController.php` (`store` y `update`) para incluir `Panadería` y `Panaderia`
- [x] 1.2 Actualizar la regla de validación de `requested_by` en `SaveConsumptionRequest.php` para incluir `Panadería` y `Panaderia`
- [x] 1.3 Actualizar el mensaje de error de área no asignada en `ConsumptionRequestController.php`

## 2. Frontend Updates

- [x] 2.1 Agregar `{ label: 'Panadería', value: 'Panadería' }` en la lista `areas` de `UserModal.vue`

## 3. Verification & Testing

- [x] 3.1 Probar creación y edición de usuario consumidor con área Panadería
- [x] 3.2 Probar registro de solicitud de consumo con usuario asignado a Panadería
