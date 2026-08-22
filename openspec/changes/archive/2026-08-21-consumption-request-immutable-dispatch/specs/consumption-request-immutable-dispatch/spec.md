## ADDED Requirements

### Requirement: Bloqueo de Edición de Cantidades en Despacho
La vista de detalle de solicitud de consumo (`/admin/consumption-requests/{id}`) SHALL mostrar la cantidad a despachar como un valor no editable (fijo/bloqueado), impidiendo que el usuario despachador modifique o aumente las cantidades aprobadas.

#### Scenario: Visualización de cantidad a despachar en escritorio y móvil
- **WHEN** un usuario con permisos de despacho accede a una solicitud aprobada
- **THEN** la columna y campo de cantidad a despachar se muestra en modo solo lectura con el cálculo automático de insumos disponibles
- **AND** no permite la edición manual de la cantidad

### Requirement: Simplificación de la Columna de Despacho
La vista de solicitud de consumo SHALL eliminar los campos de observación por ítem (`textarea`) y botones de dictado por voz de la sección de despacho, centralizando cualquier nota de despacho en la modal de confirmación general.

#### Scenario: Interfaz limpia de despacho
- **WHEN** el usuario visualiza la tabla o tarjetas de despacho
- **THEN** no se muestran botones de `+ Observación`, textareas por producto ni micrófonos de dictado en la sección de despacho
- **AND** la observación de despacho se ingresa de forma general al confirmar el despacho
