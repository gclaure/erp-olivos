## Why

When printing a consumption request receipt in roll/ticket format (80mm thermal receipt at `/admin/consumption-requests/{id}/print?format=rollo`), the quantities for requested, dispatched, and received items consistently render as zero (`0.00`) or `-`. This occurs because `receipt-roll.blade.php` references non-existent model attributes (`$detail->quantity`, `$detail->dispatched_quantity`, `$detail->received_quantity`) instead of the canonical database attributes (`quantity_requested`, `quantity_delivered`, `quantity_received`).

## What Changes

- **Fix item quantities in roll receipt**: Update `receipt-roll.blade.php` to bind to `quantity_requested`, `quantity_delivered`, and `quantity_received`.
- **Fix unit of measure abbreviation**: Reference `unitOfMeasure?->abbreviation` instead of `symbol`.
- **Fix discrepancy and observation display**: Calculate discrepancy using `abs($recvQty - $reqQty) >= 0.01` and display observation notes using `receive_observation ?? observation`.

## Capabilities

### Modified Capabilities
- `consumption-request-pdf-redesign`: Extend requirement coverage to ensure roll ticket format correctly displays quantities, units of measure, and discrepancy observations.

## Impact

- **Affected code**: `resources/views/admin/consumption-requests/receipt-roll.blade.php`.
- **APIs / Data layer**: No migrations or backend API changes needed.
