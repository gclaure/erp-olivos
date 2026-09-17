## ADDED Requirements

### Requirement: Warehouse role consumption permission synchronization
The system SHALL ensure through database migration that the `Almacén` role possesses the `manage-consumption` permission, enabling full operational visibility over internal consumption requests.

#### Scenario: Running the database migration
- **WHEN** the permission synchronization migration is executed (`php artisan migrate`)
- **THEN** the role `Almacén` is granted the permission `manage-consumption` without altering existing assignments for other roles

### Requirement: Warehouse sidebar navigation visibility
The system SHALL display the "Consumos Solicitados" item in the sidebar navigation for users possessing the `Almacén` role, while preventing access to "Registrar Consumo".

#### Scenario: Warehouse user views sidebar
- **WHEN** an authenticated user with role `Almacén` loads any administrative page
- **THEN** the sidebar displays the "Consumos" section containing "Consumos Solicitados" linked to `/admin/consumption-requests`
- **AND** the option "Registrar Consumo" is omitted from the warehouse navigation

### Requirement: Consumption requests list operational access
The system SHALL allow warehouse users to access `/admin/consumption-requests`, view pending/approved requests, filter requests, and access request details without 403 authorization blocks.

#### Scenario: Warehouse user accesses consumption requests list
- **WHEN** a warehouse operator navigates to `/admin/consumption-requests`
- **THEN** the table displays requests across branches/warehouses assigned to the operator
- **AND** the interface displays status badges, stock shortage semaphores, and links to the detail view
