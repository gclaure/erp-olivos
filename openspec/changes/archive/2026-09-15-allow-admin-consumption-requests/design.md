## Context

En el flujo de solicitudes de consumo interno (`/admin/consumption-requests/create`), el componente `CartSidebar.vue` evalúa `currentUser.area` antes de enviar la solicitud al backend. Para usuarios con roles administrativos (`Administrador`, `Super Admin`, o usuarios sin asignación fija en `users.area`), este campo es nulo, provocando que se dispare una alerta de "Perfil Incompleto" e impidiendo completar la solicitud.

## Goals / Non-Goals

**Goals:**
- Implementar un selector de área solicitante en `CartSidebar.vue` cuando `operationType === 'consumption'`.
- Si el usuario tiene un área fija en su perfil, inicializar dicha área por defecto.
- Si el usuario es Administrador o carece de área predeterminada, permitir seleccionar libremente entre las áreas operativas válidas (`Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción`, `Despacho`).
- Validar que siempre se envíe un área válida en el payload de la solicitud.
- Garantizar que el backend procese correctamente el `requested_by` enviado sin requerir que `users.area` esté poblado en la base de datos para administradores.

**Non-Goals:**
- Modificar el esquema de la base de datos (las tablas `users` y `consumption_requests` ya manejan `varchar` flexible).
- Eliminar el control de área para usuarios con rol `Consumidor` estándar (quienes deben mantener su área operativa asignada).

## Decisions

### 1. Selector reactivo de área en `CartSidebar.vue`
- **Decisión**: Se agregará una sección de selección de área en el pie del panel del carrito.
- **Comportamiento**:
  - Si el usuario tiene rol administrativo (o no tiene área fija asignada), se renderiza un dropdown elegante y responsivo con las 6 áreas disponibles.
  - Si el usuario es un consumidor regular con área asignada, se muestra su insignia de área fija (o un selector pre-cargado).
  - Al presionar "Enviar Solicitud", se valida `selectedArea.value`. Si está vacío, se alerta que debe seleccionar un área solicitante.

### 2. Sincronización con el Backend
- **Decisión**: En `ConsumptionRequestController.php`, `$requestedBy = $data['requested_by'] ?? ($user ? $user->area : null);` continuará priorizando el valor explícito enviado desde el frontend (`$data['requested_by']`).
- `SaveConsumptionRequest.php` ya valida `requested_by` con las áreas permitidas (`in:Cocina,Pastelería,Panadería,Panaderia,Eventos,Producción,Produccion,Despacho`).

## Risks / Trade-offs

- **[Riesgo]** El usuario administrador envía la solicitud sin percatarse del área por defecto.
  - *Mitigación*: Si no hay área seleccionada, exigir al usuario seleccionarla explícitamente o resaltar visualmente el área seleccionada antes del envío.
