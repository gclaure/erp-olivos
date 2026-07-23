## Why

The "Despachar Stock" button and dispatch functionality should be exclusive to the Almacén role. Currently, Admin and Administrador roles can also see and use dispatch actions, which is outside their intended scope. This needs to be restricted to match the business rule that only warehouse staff dispatch stock.

## What Changes

- **Restrict dispatch button visibility** to Almacén role only (remove Admin, Administrador, super_admin from `canUserDispatch`)
- **Restrict backend dispatch endpoint** to Almacén role only (update `dispatchRequest` authorization check)
- Admin/Administrador will no longer see the "Despachar Stock" button, the "Despachando" column, or the per-row dispatch inputs in the detail view

## Capabilities

### New Capabilities

None

### Modified Capabilities

- `consumer-consumption-catalog-ui`: Requirement changes — dispatch actions restricted to Almacén role only

## Impact

- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`: `canUserDispatch` computed property, button visibility
- `app/Http/Controllers/Admin/ConsumptionRequestController.php`: `dispatchRequest` authorization check
