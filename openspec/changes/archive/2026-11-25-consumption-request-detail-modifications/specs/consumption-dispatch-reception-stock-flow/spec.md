# Spec Delta

## MODIFIED Requirements

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
