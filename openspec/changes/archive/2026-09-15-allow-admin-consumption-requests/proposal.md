## Why

Actualmente, los administradores y usuarios sin un área operativa fija asignada en su perfil (como Super Admin o Administrador) no pueden registrar solicitudes de consumo interno desde la interfaz de POS/Consumo (`/admin/consumption-requests/create`). Al intentar enviar la solicitud, el sistema bloquea la acción con un error de "Perfil Incompleto (Tu usuario no tiene un área operativa asignada)". Esto impide que el personal administrativo registre consumos en nombre de las diferentes áreas operativas (Cocina, Pastelería, Panadería, Eventos, Producción, Despacho) o gestione requerimientos directos.

## What Changes

- Permitir la selección dinámica del área solicitante (`requested_by`) en el panel lateral del carrito de consumo (`CartSidebar.vue`) cuando el usuario tiene privilegios administrativos o no posee un área operativa fija predeterminada.
- Preseleccionar el área del usuario si ya la tiene asignada en su perfil, pero permitir a los administradores cambiar el área destino de la solicitud.
- Validar en el frontend y backend que el área seleccionada sea una de las áreas válidas (`Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción`, `Despacho`).
- Actualizar el controlador `ConsumptionRequestController` para priorizar el área enviada en la petición (`$data['requested_by']`) antes de recurrir a `$user->area`.

## Capabilities

### New Capabilities
<!-- None -->

### Modified Capabilities
- `consumer-area-options`: Se amplía el requerimiento para permitir que usuarios administradores seleccionen explícitamente el área solicitante al crear una solicitud de consumo, evitando bloqueos por perfil incompleto cuando no tienen un área fija asignada.

## Impact

- **Frontend**: `resources/js/Pages/Admin/POS/Partials/CartSidebar.vue` (interfaz de selección de área solicitante para admin/usuarios sin área fija).
- **Backend**: `app/Http/Controllers/Admin/ConsumptionRequestController.php` y `app/Http/Requests/Admin/SaveConsumptionRequest.php`.
- **Experiencia de Usuario**: Los administradores podrán despachar y registrar consumos eligiendo a qué área operativa corresponde la solicitud sin recibir alertas de perfil incompleto.
