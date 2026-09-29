# Tasks

## 1. Shortage Visibility and Reception Inputs in Show.vue

- [x] 1.1 Update missing stock banner condition to remain active in `despachado_parcial` state for Warehouse and Admin
- [x] 1.2 Enable reception quantity inputs and mandatory observation fields for all items (including 0 delivered) in desktop and mobile views of `Show.vue`
- [x] 1.3 Update `handleReceive` in `Show.vue` to process all items without filtering by `quantity_delivered > 0`
- [x] 1.4 Enable editable dispatch quantity input for Warehouse users in desktop and mobile views of `Show.vue`

## 2. Backend Reception Handling and Verification

- [x] 2.1 Verify `ConsumptionRequestService::receiveRequest` processes zero-received items with observations without exceptions
- [x] 2.2 Rebuild frontend assets with `npm run build` and verify warehouse quantity editing
