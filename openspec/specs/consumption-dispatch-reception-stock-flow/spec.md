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
The system SHALL allow Warehouse users to dispatch the available physical stock of each item (including 0 when out of stock), updating delivered quantities. After each dispatch, an item with pending quantity and no available stock SHALL be marked `pending_stock`, and an item that is complete or that received units SHALL be marked `normal`; an item that is complete SHALL NOT remain `pending_stock`. The request status SHALL be derived from requested, delivered and pending quantities: `despachado` when every item is complete, `despachado_parcial` when at least one unit has been dispatched but some item remains incomplete, and `aprobado` when no unit has been dispatched.

#### Scenario: Warehouse dispatches an order with partial stock for an item
- **WHEN** an order requests 3 units of an item and warehouse has 2 units available
- **THEN** warehouse confirms dispatch of 2 units
- **AND** the consumption request transitions to `despachado_parcial`
- **AND** the system generates an automatic purchase order for the 1 remaining missing unit

#### Scenario: Warehouse dispatches when no item has stock
- **WHEN** warehouse dispatches a request in which every item has 0 available stock
- **THEN** every item with pending quantity is marked `pending_stock`
- **AND** the consumption request remains `aprobado`
- **AND** the system generates the automatic purchase order for the missing quantities

#### Scenario: Other items dispatched while one item has no stock
- **WHEN** a request has an item with stock and another item without stock and warehouse dispatches
- **THEN** the item with stock is dispatched, the other is marked `pending_stock`
- **AND** the consumption request transitions to `despachado_parcial`

#### Scenario: Stock arrives after an item was marked pending_stock
- **WHEN** an item marked `pending_stock` receives stock through a purchase entry and warehouse dispatches again
- **THEN** the available units are dispatched and the delivered quantity is updated
- **AND** the item returns to `normal` once its requested quantity is fully delivered
- **AND** the request status is recalculated

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
