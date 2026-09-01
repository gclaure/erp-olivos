## ADDED Requirements

### Requirement: Edición de cantidad solicitada por Administrador antes del despacho
El rol Administrador (`Admin`, `Administrador`, `is_super_admin`) SHALL poder modificar la cantidad solicitada (`quantity_requested`) de cualquier ítem en una solicitud de consumo cuando la solicitud se encuentre en etapa de revisión/aprobación (`pendiente`, `observado`). El sistema MUST validar que la nueva cantidad sea numérica y estrictamente mayor a 0 (`quantity_requested > 0`).

#### Scenario: Administrador modifica cantidad solicitada de un producto
- **WHEN** un Administrador edita la cantidad solicitada de un producto de 10 a 5 en una solicitud pendiente
- **THEN** el sistema actualiza `quantity_requested` a 5 en el detalle correspondiente
- **AND** recalcula las métricas de faltantes y stock

#### Scenario: No administrador intenta modificar cantidad solicitada
- **WHEN** un usuario con rol Consumidor o Almacén intenta modificar la cantidad solicitada de un producto
- **THEN** el sistema MUST rechazar la petición con error 403 No autorizado

### Requirement: Cancelación exclusiva por Administrador
La cancelación de una solicitud de consumo SHALL ser una acción exclusiva del rol Administrador (`Admin`, `Administrador`, `is_super_admin`) para solicitudes en estado `pendiente`, `observado` o `aprobado` (antes de haber sido despachadas o entregadas). Se MUST exigir un motivo de cancelación obligatorio con un mínimo de 5 caracteres.

#### Scenario: Administrador cancela solicitud pendiente con motivo válido
- **WHEN** un Administrador cancela una solicitud en estado `pendiente` con motivo "Insumo descontinuado por gerencia"
- **THEN** el sistema actualiza el estado a `cancelado`
- **AND** registra `cancelled_by_user_id`, `cancelled_at` y `cancellation_notes`

#### Scenario: Usuario no administrador intenta cancelar solicitud
- **WHEN** un usuario con rol Consumidor intenta cancelar una solicitud
- **THEN** el sistema MUST rechazar la operación indicando que solo el Administrador puede cancelar solicitudes
