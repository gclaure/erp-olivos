## ADDED Requirements

### Requirement: Detailed consumption request status breakdown
The system SHALL display an enriched status indication for consumption requests in both the desktop table and mobile cards of `/admin/consumption-requests`, specifying the operational sub-phase and the pending actor.

#### Scenario: Pending request awaiting administrator approval
- **WHEN** a consumption request has status "pendiente" and has not been approved (`approved_at` is null)
- **THEN** the status cell displays "Por Aprobar" with an indication that action is required from Administración

#### Scenario: Approved request ready for warehouse dispatch
- **WHEN** a consumption request has been approved (`approved_at` is not null or status is "aprobado") and is not yet dispatched
- **THEN** the status cell displays "Aprobado" with an indication that it is ready for preparation in Almacén

#### Scenario: Request with missing warehouse stock
- **WHEN** a pending or partially dispatched consumption request has items exceeding available physical stock (`has_missing_stock` is true)
- **THEN** the status cell displays an explicit warning indicator "Falta Stock" / "Requiere Compra"

#### Scenario: Dispatched request awaiting user reception
- **WHEN** a consumption request has status "despachado"
- **THEN** the status cell displays "Despachado" with an indicator that the requester must confirm reception

#### Scenario: Partially dispatched request
- **WHEN** a consumption request has status "despachado_parcial" or "parcial"
- **THEN** the status cell displays "Despacho Parcial" with progress information
