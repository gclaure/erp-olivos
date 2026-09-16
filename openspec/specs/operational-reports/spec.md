# Capability: Operational Reports

## Requirements

### Requirement: Operational Reports Module Navigation and Layout
The system SHALL replace the previous generic BI sales dashboard with an operational reports module organized with responsive tabs: "Consumos por Consumidor", "Entradas y Salidas Mensuales", and "Stock de Insumos".

#### Scenario: User visits the reports index page
- **WHEN** an administrator or warehouse staff navigates to `/admin/reports`
- **THEN** the system displays the operational reports view with three distinct tabs and filter controls without rendering any deprecated BI charts.

### Requirement: Consumer Consumption Report Filtering and Visualization
The system SHALL allow users to filter consumption requests by consumer (requester user), date range (or monthly period), warehouse/branch, and request status, displaying the summary metrics and detailed list of items consumed.

#### Scenario: Filtering consumptions by user and date range
- **WHEN** the user selects a specific consumer and specifies a date range
- **THEN** the system updates the table to show all consumption requests and items dispatched to that consumer within that timeframe with quantities and dates.

### Requirement: Consumer Consumption Exporting
The system SHALL allow exporting the filtered consumer consumption report to PDF format and Excel (`.xlsx`) format.

#### Scenario: Exporting consumer consumptions to PDF
- **WHEN** the user applies filters and clicks the "Exportar PDF" button in the Consumos tab
- **THEN** the system generates and streams a formatted PDF document containing the consumer consumption summary and itemized details.

#### Scenario: Exporting consumer consumptions to Excel
- **WHEN** the user applies filters and clicks the "Exportar Excel" button in the Consumos tab
- **THEN** the system downloads an `.xlsx` spreadsheet with styled headers and detailed consumption records.

### Requirement: Monthly Warehouse Movements (Entradas y Salidas)
The system SHALL provide a report of monthly inventory entries (purchases, positive adjustments) and exits (consumption dispatches, adjustments) based on Kardex records, filtered by period, warehouse, and movement type.

#### Scenario: Viewing monthly movements for a warehouse
- **WHEN** the warehouse manager selects a specific month and warehouse
- **THEN** the system renders total quantities and values for entries and exits along with the itemized movement list.

#### Scenario: Exporting monthly movements to PDF and Excel
- **WHEN** the user clicks "Exportar PDF" or "Exportar Excel" in the Entradas y Salidas tab
- **THEN** the system downloads the corresponding document with the selected period's movements and summary totals.

### Requirement: Warehouse Supplies Stock Report
The system SHALL provide an inventory stock report with an explicit filter toggle between "Stock mayor a cero" (`stock > 0`) and "Todos los productos" (including 0 stock), plus warehouse and category filters.

#### Scenario: Filtering stock greater than zero
- **WHEN** the warehouse manager selects the "Stock mayor a cero" filter option
- **THEN** the system lists only items where current inventory is greater than zero for the chosen warehouse.

#### Scenario: Exporting stock report to PDF and Excel
- **WHEN** the user clicks "Exportar PDF" or "Exportar Excel" in the Stock tab
- **THEN** the system downloads the inventory stock report in PDF or Excel format reflecting the exact stock condition filter applied.
