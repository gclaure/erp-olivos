# Spec Delta

## Purpose

Garantiza la visualización íntegra y accesible del catálogo de productos en pantallas de 13 pulgadas y laptops, permitiendo desplazamiento horizontal fluido y manteniendo visible la columna de acciones.

## ADDED Requirements

### Requirement: Visible and accessible horizontal scrollbar
The system SHALL provide a visible, draggable horizontal scrollbar with appropriate track and thumb styling for tables with `overflow-x-auto`, allowing users on desktop and laptop viewports (including 13-inch displays) to smoothly pan horizontally across all columns.

#### Scenario: User navigates products table on a 13-inch display
- **WHEN** an administrator views the products catalog on a viewport between 1280px and 1440px with a persistent sidebar
- **THEN** the products table wrapper displays a visible horizontal scrollbar
- **AND** the user can scroll horizontally to inspect columns that extend beyond the initial viewport width

### Requirement: Sticky actions column
The system SHALL pin the "Acciones" column to the right boundary of the products table (`sticky right-0`) with an opaque background matching the row theme (white in light mode, dark surface in dark mode) and a subtle elevation shadow, ensuring the edit and delete action buttons remain permanently visible and clickable during horizontal scrolling.

#### Scenario: User scrolls table horizontally
- **WHEN** the user scrolls the products table to the left or right
- **THEN** the "Acciones" header and row cells remain fixed at the right edge of the visible table container
- **AND** underlying columns scroll smoothly beneath the sticky actions column without visual overlapping artifacts
- **AND** the edit and delete buttons in each row remain immediately accessible

### Requirement: Table column proportions and minimum width
The system SHALL enforce a structured minimum width (`min-w-[1020px]`) on the desktop table element to prevent column crushing, ensuring code, product title, warehouse tags, stock, packaging metrics, unit, status, and actions are legible.

#### Scenario: Displaying all 8 product columns
- **WHEN** the products table is rendered on a viewport width where all columns cannot fit simultaneously
- **THEN** the table preserves its column proportions without wrapping or truncating numerical data inappropriately
- **AND** the overflow is cleanly handled by the horizontal scroll container
