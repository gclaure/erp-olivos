## MODIFIED Requirements

### Requirement: Quantity breakdown and discrepancy visibility in PDF
The PDF receipt generated at `/admin/consumption-requests/{id}/print` (in both letter layout and 80mm roll format) SHALL display requested, dispatched, and received quantities for every item in the consumption request.

#### Scenario: Printing request with complete delivery and reception
- **WHEN** a user prints a request that has been delivered and received
- **THEN** the items table displays columns or rows for "Solicitado", "Despachado", and "Recibido" with corresponding numeric quantities and units

#### Scenario: Printing request with reception discrepancy
- **WHEN** an item has a difference between requested and received quantity and contains an observation note
- **THEN** the PDF displays the received quantity highlighted and includes the discrepancy reason below the product description

#### Scenario: Printing request in roll/ticket format
- **WHEN** a user prints a consumption request using the roll format (`format=rollo`)
- **THEN** each item row correctly displays the requested quantity (`quantity_requested`), dispatched quantity (`quantity_delivered`), and received quantity (`quantity_received`), with fallback dashes when not applicable rather than defaulting to `0.00`
