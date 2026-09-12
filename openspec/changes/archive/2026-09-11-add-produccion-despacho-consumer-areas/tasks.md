## 1. Backend Validations and Messages

- [x] 1.1 Actualizar las reglas de validación en `UserController.php` (`store` y `update`) para incluir `Producción`, `Produccion` y `Despacho`
- [x] 1.2 Actualizar la regla de validación de `requested_by` en `SaveConsumptionRequest.php` para incluir `Producción`, `Produccion` y `Despacho`
- [x] 1.3 Actualizar el mensaje de advertencia sobre áreas operativas en `ConsumptionRequestController.php`

## 2. Frontend Components

- [x] 2.1 Agregar las opciones `Producción` y `Despacho` al array `areas` en `UserModal.vue`
- [x] 2.2 Actualizar el texto del mensaje de alerta de área operativa en `CartSidebar.vue`

## 3. Verification

- [x] 3.1 Probar validación backend creando/editando un usuario con área `Producción` y `Despacho`
- [x] 3.2 Verificar renderizado del selector de áreas en la interfaz de usuario
