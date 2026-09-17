## ADDED Requirements

### Requirement: Semáforo de disponibilidad de stock en listado de solicitudes
El listado de solicitudes de consumo (`/admin/consumption-requests`) SHALL mostrar para cada solicitud una columna o badge indicador de disponibilidad inmediata de stock en el almacén de origen.

#### Scenario: Visualización de disponibilidad completa (100%)
- **WHEN** todos los insumos de una solicitud de consumo cuentan con stock físico suficiente en el almacén (`stock_available >= quantity_requested`)
- **THEN** el listado muestra un badge verde "🟢 Stock Disponible" (o "100% Stock")

#### Scenario: Visualización de faltante parcial o total de stock
- **WHEN** uno o más insumos tienen stock insuficiente o cero en el almacén
- **THEN** el listado muestra un badge ámbar/rojo indicando la cantidad de ítems faltantes (ej. "⚠️ Faltan X insumos" o "🔴 Sin Stock")

### Requirement: Panel destacado de alertas de faltantes en la vista de detalle
La vista de detalle de la solicitud de consumo (`/admin/consumption-requests/{id}`) SHALL presentar para los roles con permisos de almacén o administración un panel superior de advertencia de faltantes con acceso a compras.

#### Scenario: Almacenero consulta solicitud con faltantes
- **WHEN** un usuario con rol Almacén o Administrador accede a una solicitud que contiene insumos con `stock_available < quantity_requested`
- **THEN** se muestra un panel superior informativo con el listado detallado de productos faltantes, stock actual y déficit
- **AND** proporciona una acción directa para generar la Solicitud de Compra (`PurchaseOrder`)
