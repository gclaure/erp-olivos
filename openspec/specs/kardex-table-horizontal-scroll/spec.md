# kardex-table-horizontal-scroll Specification

## Purpose

Permite una navegación contable accesible, clara y ergonómica en el Kardex de inventario en pantallas de 13 pulgadas y laptops, garantizando desplazamiento horizontal visible y controles asistidos.

## Requirements

### Requirement: Visible and accessible horizontal scrollbar on Kardex table
The system SHALL provide a visible, draggable horizontal scrollbar with appropriate track and thumb styling for the Kardex table wrapper, allowing users on desktop and laptop viewports (including 13-inch displays) to smoothly pan horizontally across all 13 accounting columns.

#### Scenario: User navigates Kardex table on a 13-inch laptop
- **WHEN** an administrator or warehouse user views `/admin/kardex` on a viewport between 1280px and 1440px with a persistent sidebar
- **THEN** the Kardex table wrapper displays a permanent, visible horizontal scrollbar
- **AND** the user can drag or scroll horizontally to inspect Outflows (Salidas) and Total Balances (Saldos Totales) that extend beyond the screen width

### Requirement: Accounting table minimum width
The system SHALL enforce a structured minimum width (`min-w-[1380px]`) on the desktop Kardex `<table>` element to prevent column compression, guaranteeing that timestamps, product SKU badges, descriptions, quantities, unit costs, and total costs remain fully legible.

#### Scenario: Preserving accounting data formatting
- **WHEN** the Kardex table is rendered on any viewport
- **THEN** monetary amounts and quantities retain their `whitespace-nowrap` formatting without wrapping or text overlap
- **AND** all two-level table headers remain structurally aligned with their respective sub-columns

### Requirement: Assisted horizontal scroll controls
The system SHALL provide navigation controls (`‹` and `›` buttons) alongside a helper label above the Kardex table, allowing the user to smoothly scroll the table left or right by a fixed distance with a single click.

#### Scenario: User clicks horizontal scroll button
- **WHEN** the user clicks the right chevron button (`›`) above the Kardex table
- **THEN** the table container smoothly scrolls horizontally towards the Salidas and Saldos Totales columns
- **AND** clicking the left chevron button (`‹`) returns smoothly towards the Fecha and Producto columns
