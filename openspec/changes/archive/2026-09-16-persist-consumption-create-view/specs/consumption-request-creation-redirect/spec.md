## ADDED Requirements

### Requirement: Redirección a la vista de creación tras guardar solicitud de consumo
El endpoint `POST /admin/consumption-requests` (`ConsumptionRequestController::store`) SHALL retornar una redirección hacia la ruta `admin.consumption-requests.create` al completar exitosamente el registro de una o más solicitudes de consumo.

#### Scenario: Registro exitoso de una solicitud de consumo
- **WHEN** un usuario registra una solicitud de consumo válida desde la ruta `/admin/consumption-requests/create`
- **THEN** el sistema procesa el guardado en base de datos
- **AND** redirige la respuesta a la ruta `admin.consumption-requests.create`
- **AND** envía en la sesión flash el mensaje `success` y el arreglo `success_data` con el `id` de la primera solicitud creada

#### Scenario: Apertura del comprobante y reseteo del carrito en la interfaz de creación
- **WHEN** Inertia.js recibe la respuesta exitosa en `/admin/consumption-requests/create`
- **THEN** el componente `Admin/POS/Index.vue` ejecuta el callback `onSuccess`
- **AND** abre la URL de impresión `/admin/consumption-requests/{id}/print` en una nueva pestaña del navegador
- **AND** reinicia el estado reactivo del carrito (`clearCart`) permitiendo registrar inmediatamente un nuevo consumo
