## ADDED Requirements

### Requirement: Product card detail action
The system SHALL provide a dedicated action button on each product card within the POS / consumption request catalog to view the full product specifications without adding it to the cart.

#### Scenario: Clicking product details button
- **WHEN** the user clicks the info button on a product card
- **THEN** the product detail modal opens with the selected product's information and the card's add-to-cart click event is prevented

### Requirement: Product detail modal presentation
The system SHALL display a modal containing product specifications including image, code, name, type, categories, unit of measure, package presentation, brand, and location.

#### Scenario: Display product details for Consumer role
- **WHEN** a user with role "Consumidor" views the product detail modal
- **THEN** the modal displays availability as "Disponible" or "No disponible" without exposing raw numeric warehouse stock

#### Scenario: Display product details for Administrator or Warehouse role
- **WHEN** an administrator or warehouse user views the product detail modal
- **THEN** the modal displays the exact numeric available stock and reserved quantities

### Requirement: Add to cart from detail modal
The system SHALL allow adding the product to the active cart / request directly from the product detail modal with a quantity selector.

#### Scenario: Adding item from modal
- **WHEN** the user selects a quantity and clicks "Agregar al Pedido" in the modal
- **THEN** the product is added to the cart with the selected quantity and the modal is dismissed
