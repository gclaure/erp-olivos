# Admin Zero Stock Consumption Spec

## Requirements

### Requirement: Admin product card interaction with zero stock
The system SHALL allow users with role `Administrador` or `Super Administrador` in consumption mode (`operationType === 'consumption'`) to interact with product cards having zero or insufficient stock without visual disability or disabled cursors, while preserving the exact numeric stock badge and reservation indicator.

#### Scenario: Administrator views and clicks on an out-of-stock product card
- **WHEN** an Administrator accesses `/admin/consumption-requests/create` and views a product with 0 available stock
- **THEN** the card displays the numeric stock badge (`0` in red) and reservations (`Res: X`)
- **AND** the card remains interactive without `opacity-60` or `cursor-not-allowed`
- **AND** clicking the card opens the product detail modal

### Requirement: Admin product detail modal zero stock addition
The system SHALL allow users with role `Administrador` or `Super Administrador` to select any desired quantity and add out-of-stock products to the cart in `ProductDetailModal.vue` when in consumption mode, while clearly displaying the real available stock and reservation quantities.

#### Scenario: Administrator opens modal for an out-of-stock product
- **WHEN** an Administrator opens `ProductDetailModal.vue` for a product with 0 stock in consumption mode
- **THEN** the modal displays "Stock Disponible: 0 [unidad]" and reservation notes
- **AND** the quantity stepper is unlocked with maximum limit set to 99999
- **AND** the button "Agregar a la Solicitud" is active and functional

### Requirement: Admin cart submission without stock blockage
The system SHALL display informative stock shortage warnings in `CartSidebar.vue` for each affected item while keeping the "Enviar Solicitud de Consumo" button fully enabled for Administrators.

#### Scenario: Administrator submits consumption cart containing out-of-stock items
- **WHEN** an Administrator has out-of-stock items in the consumption cart
- **THEN** each item displays an informative warning "Stock insuficiente: X disp."
- **AND** the submit button is enabled with text "Enviar Solicitud de Consumo" (or "Guardar Cambios de Solicitud")
- **AND** clicking submit successfully triggers the request submission

### Requirement: Backend consumption request authorization for Administrator role
The system SHALL allow `SaveConsumptionRequest` to validate and persist consumption requests created by Administrators even if requested quantities exceed available physical stock or if available stock is zero.

#### Scenario: Administrator submits consumption request to backend
- **WHEN** an Administrator sends a consumption request containing items where requested quantity exceeds available stock
- **THEN** `SaveConsumptionRequest` validates successfully without adding errors to `cart.*.quantity`
- **AND** the consumption request is stored in the database in `pendiente` status
