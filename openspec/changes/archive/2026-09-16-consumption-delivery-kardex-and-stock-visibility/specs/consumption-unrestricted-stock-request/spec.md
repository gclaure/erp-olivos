## MODIFIED Requirements

### Requirement: Unrestricted quantity requests in consumption mode
The system SHALL allow consumers and operators to request or edit any quantity of products without being blocked by insufficient or zero warehouse stock.

#### Scenario: Requesting items with zero available stock
- **WHEN** a user creates or edits a consumption request (`operationType === 'consumption'`) and selects a product with 0 or insufficient available stock
- **THEN** the system allows adding the product to the cart and does not block submission or display red error borders
- **AND** the product cards in `ProductCard.vue` remain active and interactive without `opacity-60` or `cursor-not-allowed`

#### Scenario: Submitting consumption request with stock shortages
- **WHEN** the user submits or updates the consumption request
- **THEN** the submit button remains enabled ("Enviar Solicitud de Consumo" or "Guardar Cambios de Solicitud") and successfully submits the request to the server

### Requirement: Product detail modal permits adding to consumption
The system SHALL allow adding a product to the cart in `ProductDetailModal.vue` when `operationType === 'consumption'`, even if available stock is zero.

#### Scenario: Consumer opens detail modal of an out-of-stock product
- **WHEN** `operationType === 'consumption'` and `ProductDetailModal` is opened for a product with 0 stock
- **THEN** the modal displays the quantity selector and an active "Agregar a la Solicitud" button

### Requirement: Complete stock abstraction for consumption / consumer role
The system SHALL hide all stock shortages, quantities and reservations from consumers in `ProductCard.vue` and `ProductDetailModal.vue`, displaying a uniform "Disponible" status badge.

#### Scenario: Displaying product status to consumers
- **WHEN** a product is rendered in consumption mode (`operationType === 'consumption'`) or for a consumer user
- **THEN** the badge always indicates "Disponible" in emerald green regardless of physical stock
- **AND** reservation indicators and out-of-stock notices are not displayed to the consumer
