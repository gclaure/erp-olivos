## Why

Actualmente los usuarios con rol Consumidor pueden ser asignados únicamente a las áreas "Cocina", "Pastelería" y "Eventos". La operación del negocio requiere incorporar el área "Panadería" como un departamento válido de consumidor para que los colaboradores de este sector puedan registrar sus solicitudes de consumo interno y asociarlas a su área operativa correspondiente.

## What Changes

- Agregar la opción `Panadería` a la lista de selección de Área/Departamento en la creación y edición de usuarios con rol Consumidor (`UserModal.vue`).
- Actualizar las reglas de validación en `UserController` (`store` y `update`) para admitir `Panadería` (y `Panaderia`).
- Actualizar las reglas de validación en `SaveConsumptionRequest` para admitir `Panadería` (y `Panaderia`) en `requested_by`.
- Actualizar el mensaje de error de área no asignada en `ConsumptionRequestController@store` para incluir `Panadería`.

## Capabilities

### New Capabilities
- `consumer-area-options`: Define la lista oficial de áreas y departamentos operativos permitidos para usuarios con rol Consumidor (`Cocina`, `Pastelería`, `Panadería`, `Eventos`) y su validación en el ciclo de consumo interno.

## Impact

- **Frontend:** `resources/js/Pages/Admin/Users/Partials/UserModal.vue`.
- **Backend Controllers & Requests:** `app/Http/Controllers/Admin/UserController.php`, `app/Http/Requests/Admin/SaveConsumptionRequest.php`, `app/Http/Controllers/Admin/ConsumptionRequestController.php`.
