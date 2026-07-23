## Why

Consumidores viewing consumption request details should not see physical stock quantities (`Stock Físico`). This is an operational number relevant only to warehouse and admin roles. Showing it to consumers creates confusion and exposes internal inventory data unnecessarily.

## What Changes

- **Hide Stock Físico column from consumers** in the consumption request detail view (desktop table header + cell, mobile grid cell)
- Admin/Almacén/Administrador views remain unchanged (they still see Stock Físico)
- Status badges (DISPONIBLE, SIN STOCK, PARCIAL, ENVIADO, ENTREGADO) are NOT changed in this iteration

## Capabilities

### New Capabilities

None

### Modified Capabilities

- `consumer-consumption-catalog-ui`: Requirement changes — consumer role no longer sees Stock Físico column in the request detail view (Show.vue)

## Impact

- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`: desktop table + mobile grid conditionals only
