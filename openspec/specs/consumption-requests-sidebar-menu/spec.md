## ADDED Requirements

### Requirement: Consumos sidebar menu group
The system SHALL provide a dedicated top-level collapsible menu group named "Consumos" in the administrative sidebar containing consumption request actions.

#### Scenario: Display menu for Administrator
- **WHEN** an Administrator or Super Admin views the sidebar
- **THEN** the sidebar displays the "Consumos" menu item with children "Registrar Consumo" (`admin.consumption-requests.create`) and "Consumos Solicitados" (`admin.consumption-requests.index`)

#### Scenario: Display menu for Consumer role
- **WHEN** a user with the "Consumidor" role views the sidebar
- **THEN** the sidebar displays the "Consumos" menu item with children "Registrar Consumo" and "Consumos Solicitados"

#### Scenario: Display menu for Warehouse role
- **WHEN** a user with the "Almacén" role views the sidebar
- **THEN** the sidebar displays the "Consumos" menu item containing only "Consumos Solicitados" (excluding "Registrar Consumo")

### Requirement: Inventory menu decoupling
The system SHALL remove the consumption request routes (`admin.consumption-requests.create` and `admin.consumption-requests.index`) from the "Inventario" menu group.

#### Scenario: Inventory menu items verification
- **WHEN** an authorized user expands the "Inventario" menu
- **THEN** only general inventory items (Productos, Categorías, Unidades de Medida, Almacenes, Kardex, Mermas) are displayed, and no consumption request items appear within it
