## Why

Actualmente, los usuarios con rol Consumidor pueden ser asignados a las áreas operativas "Cocina", "Pastelería", "Panadería" y "Eventos". La operación del negocio requiere incorporar las áreas "Producción" y "Despacho" como departamentos válidos de consumidor para que los colaboradores de estos sectores puedan gestionar usuarios y registrar sus solicitudes de consumo interno asociadas a su área operativa correspondiente.

## What Changes

- Incorporar `Producción` y `Despacho` como opciones seleccionables en el selector de Área del formulario modal de usuarios (`/admin/users`).
- Actualizar las reglas de validación en `UserController` (`store` y `update`) para admitir `Producción`, `Produccion` y `Despacho`.
- Actualizar las reglas de validación en `SaveConsumptionRequest` para admitir `Producción`, `Produccion` y `Despacho` en el campo `requested_by`.
- Actualizar los mensajes de validación e informativos en `ConsumptionRequestController` y `CartSidebar.vue` para reflejar la lista completa de áreas operativas.

## Capabilities

### New Capabilities
<!-- None -->

### Modified Capabilities
- `consumer-area-options`: Amplía la lista oficial de áreas y departamentos operativos permitidos para usuarios con rol Consumidor incorporando `Producción` y `Despacho`.

## Impact

- **Frontend**: Componente `UserModal.vue` (`/admin/users`) y `CartSidebar.vue` (POS de consumo interno).
- **Backend**: `UserController.php`, `SaveConsumptionRequest.php`, `ConsumptionRequestController.php`.
- **Base de Datos**: Ningún cambio de esquema requerido ya que las columnas `users.area` y `consumption_requests.requested_by` son de tipo `varchar`.
