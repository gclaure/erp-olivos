## 1. Frontend - Interfaz de Selección de Área en POS / Carrito

- [x] 1.1 Agregar estado reactivo `selectedArea` y lista de áreas operativas (`Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción`, `Despacho`) en `CartSidebar.vue`.
- [x] 1.2 Implementar selector desplegable responsivo de área en `CartSidebar.vue` cuando el usuario sea Administrador o no tenga área fija asignada.
- [x] 1.3 Adaptar el método `submitConsumption` en `CartSidebar.vue` para enviar `selectedArea` en el evento `submit-consumption` y validar que un área esté seleccionada antes de enviar.

## 2. Backend - Validación y Control de Solicitudes

- [x] 2.1 Verificar y asegurar que `SaveConsumptionRequest.php` y `ConsumptionRequestController.php` acepten y almacenen correctamente el `requested_by` enviado por usuarios administradores.
- [x] 2.2 Probar el flujo completo de creación de solicitud de consumo con usuario administrador y verificar que no aparezcan alertas de perfil incompleto ni errores de validación.
