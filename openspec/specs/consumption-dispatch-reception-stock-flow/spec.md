# Capability: Consumption Dispatch Reception Stock Flow

## Purpose

Permite gestionar el ciclo operativo completo de solicitudes de consumo interno cuando existen insumos con stock parcial o en cero, asegurando la visibilidad de faltantes para Almacén y permitiendo la recepción conforme documentada en Cocina.

## Requirements

### Requirement: Warehouse shortage visibility in pending and partial states
The system SHALL display the missing stock alert banner and physical stock indicators to Warehouse and Administrator roles whenever a consumption request has insufficient stock, in states `pendiente`, `aprobado`, `observado` and `despachado_parcial`.

#### Scenario: Warehouse user views a consumption request with stock shortages
- **WHEN** a user with role `Almacén` or `Administrador` views a consumption request where one or more items have available physical stock lower than requested quantity
- **THEN** the system displays the "Insumos Faltantes Detectados en Almacén" alert banner with the total missing units
- **AND** the items table displays the real physical stock and the status badge `STOCK PARCIAL` or `SIN STOCK`

### Requirement: Warehouse partial dispatch execution
The system SHALL allow Warehouse users to dispatch the available physical stock of each item (including 0 when out of stock), updating delivered quantities and setting request status to `despachado_parcial` when items remain incomplete.

#### Scenario: Warehouse dispatches an order with partial stock for an item
- **WHEN** an order requests 3 units of an item and warehouse has 2 units available
- **THEN** warehouse confirms dispatch of 2 units
- **AND** the consumption request transitions to `despachado_parcial`
- **AND** the system generates an automatic purchase order for the 1 remaining missing unit

### Requirement: Consumer reception input and discrepancy observation for all items
The system SHALL provide input fields and mandatory discrepancy observation fields in `Show.vue` for all items in `despachado` and `despachado_parcial` status, including items where delivered quantity is zero.

#### Scenario: Consumer receives a delivery where an item arrived in zero
- **WHEN** the consumer (Kitchen) confirms reception of a request where an item has `quantity_delivered = 0`
- **THEN** the interface displays the received quantity input initialized to 0
- **AND** displays the observation textarea allowing the user to record the reason for zero delivery
- **AND** submitting the reception sends the received quantity and observation to the backend

### Requirement: Backend reception confirmation with zero-delivered items
The system SHALL allow `ConsumptionRequestService::receiveRequest` to complete reception when items have quantity 0 or different from requested, provided a discrepancy observation is supplied, transitioning the request to `entregado` and recording Kardex exits only for items with received quantity greater than 0.

#### Scenario: Backend processes reception with zero-received item
- **WHEN** the backend receives a reception payload with an item having received quantity 0 and a valid observation note
- **THEN** the transaction updates `quantity_received = 0` and stores the observation
- **AND** the overall request status updates to `entregado`
- **AND** no Kardex exit is recorded for the item with 0 quantity
