## MODIFIED Requirements

### Requirement: Dispatch action role restriction

Dispatch actions (Despachar Stock button, Despachando column, per-row dispatch inputs, mobile dispatch section) SHALL be visible and usable only by users with the Almacén role. Admin, Administrador, and super_admin roles SHALL NOT see or use dispatch functionality.

#### Scenario: Almacén user views consumption request detail
- Given the user has the Almacén role
- And the request status is pendiente, aprobado, or despachado_parcial
- When they view the request detail page
- Then the Despachar Stock button is visible
- And the Despachando column and per-row dispatch inputs are visible

#### Scenario: Admin user views consumption request detail
- Given the user has the Admin or Administrador role
- When they view the request detail page
- Then the Despachar Stock button is hidden
- And the Despachando column and per-row dispatch inputs are hidden

#### Scenario: Backend dispatch authorization
- Given a user without the Almacén role
- When they attempt to call the dispatch endpoint
- Then the request is rejected with an authorization error
