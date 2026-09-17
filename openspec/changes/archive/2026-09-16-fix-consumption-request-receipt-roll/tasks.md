## 1. Roll Receipt Template Fixes

- [x] 1.1 Update item attribute bindings in `resources/views/admin/consumption-requests/receipt-roll.blade.php` to use `quantity_requested`, `quantity_delivered`, and `quantity_received`
- [x] 1.2 Update unit of measure abbreviation retrieval in `receipt-roll.blade.php`
- [x] 1.3 Fix discrepancy calculation and observation notes rendering in `receipt-roll.blade.php`

## 2. Verification

- [x] 2.1 Verify PDF generation and streaming for roll format at `/admin/consumption-requests/{id}/print?format=rollo`
