## MODIFIED Requirements

### Requirement: Stock Físico visibility in request detail view

Consumers SHALL NOT see the Stock Físico column (header, data cell, mobile grid cell) in the consumption request detail view (`Show.vue`). The column remains visible for Almacén, Admin, and Administrador roles.

#### Scenario: Consumer opens consumption request detail
- Given the user has the Consumidor role
- When they view the request detail page
- Then the Stock Físico column (desktop header, desktop cell, mobile grid cell) is hidden
- And the remaining columns (Producto, Almacén, Solicitado, Estado, mobile equivalents) remain visible

#### Scenario: Admin/Almacén opens consumption request detail
- Given the user has the Almacén, Admin, or Administrador role
- When they view the request detail page
- Then the Stock Físico column is visible as before

### Requirement: Status badge visibility

Status badges (DISPONIBLE, SIN STOCK, PARCIAL, ENVIADO, ENTREGADO) SHALL remain visible to consumers with their original colors in this iteration.

#### Scenario: Consumer views status badge
- Given the user has the Consumidor role
- When they view the request detail page
- Then DISPONIBLE/SIN STOCK/PARCIAL/ENVIADO/ENTREGADO badges are shown with original colors
