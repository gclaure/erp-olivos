## ADDED Requirements

### Requirement: Collision-free product card elements
The system SHALL layout elements within `ProductCard.vue` so that interactive buttons (such as product details modal trigger) and informational badges (such as stock and availability indicators) occupy dedicated non-overlapping visual zones.

#### Scenario: Displaying product details trigger without overlap
- **WHEN** the product card is rendered in desktop, tablet, or mobile view in any column width
- **THEN** the "Detalles" button is located in the product information area next to the product code, and does not overlap with the stock or availability badge located in the top-right corner of the image

#### Scenario: Triggering the product detail modal from the new position
- **WHEN** user clicks on the "Detalles" button in the information row
- **THEN** the card does not trigger the default add-to-cart click event (`@click.stop`), and emits `show-detail` with the current product payload
