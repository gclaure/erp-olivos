## ADDED Requirements

### Requirement: Quantity breakdown and discrepancy visibility in PDF
The PDF receipt generated at `/admin/consumption-requests/{id}/print` SHALL display requested, dispatched, and received quantities for every item in the consumption request.

#### Scenario: Printing request with complete delivery and reception
- **WHEN** a user prints a request that has been delivered and received
- **THEN** the items table displays columns for "Solicitado", "Despachado", and "Recibido" with corresponding numeric quantities and units

#### Scenario: Printing request with reception discrepancy
- **WHEN** an item has a difference between requested and received quantity and contains an observation note
- **THEN** the PDF displays the received quantity highlighted and includes the discrepancy reason below the product description

### Requirement: Operational lifecycle traceability in PDF
The PDF receipt SHALL display the actors and timestamps for every stage in the lifecycle (Created by, Approved by, Dispatched by, Received by).

#### Scenario: Displaying full lifecycle signatures and metadata
- **WHEN** a consumption request is printed
- **THEN** the document displays the responsible users and dates in a structured metadata box, along with three signature areas for Requesting User, Warehouse Dispatcher, and Destination Reception

### Requirement: Premium editorial visual design
The PDF receipt SHALL render using clean typography, high-contrast Slate color hierarchy, balanced margins, crisp tabular numbers, and corporate branding compatible with DomPDF.

#### Scenario: Rendering PDF on letter size
- **WHEN** the PDF is generated and streamed to the browser or downloaded
- **THEN** it renders with letter portrait layout, clean table lines, status badges, and institutional footer without visual clipping or overflow

### Requirement: Cabecera balanceada con logotipo centrado
El encabezado corporativo del PDF SHALL ubicar el logotipo en la posición central para máxima nitidez y balance estético.

#### Scenario: Visualización del logotipo en la cabecera del comprobante
- **WHEN** se genera el PDF de consumo
- **THEN** el logo se presenta en el centro de la cabecera sin comprimirse contra los textos laterales
