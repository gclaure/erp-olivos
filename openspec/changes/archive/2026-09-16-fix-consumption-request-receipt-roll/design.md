## Context

The roll format consumption receipt template (`resources/views/admin/consumption-requests/receipt-roll.blade.php`) was referencing outdated property names on the `ConsumptionRequestDetail` model (`quantity`, `dispatched_quantity`, `received_quantity`, `has_discrepancy`, and `unitOfMeasure->symbol`). As a result, quantities always displayed as `0.00` or `-`. The letter format receipt (`receipt.blade.php`) already uses the correct attributes (`quantity_requested`, `quantity_delivered`, `quantity_received`, `unitOfMeasure->abbreviation`, and `receive_observation ?? observation`).

## Goals / Non-Goals

**Goals:**
- Correct attribute bindings in `receipt-roll.blade.php` to match `App\Models\ConsumptionRequestDetail`.
- Accurately display requested, dispatched, and received quantities according to the request lifecycle status.
- Ensure unit of measure abbreviations and discrepancy observations are formatted cleanly on 80mm thermal paper.

**Non-Goals:**
- Modifying backend controller logic, database schema, or letter receipt template.
- Changing POS or consumption request business workflows.

## Decisions

- **Direct attribute alignment with `ConsumptionRequestDetail`**:
  - `quantity_requested`: cast to float, formatted with 2 decimal places.
  - `quantity_delivered`: if status is pending/approved/observed, display `-`; otherwise format numeric quantity.
  - `quantity_received`: display formatted numeric value when present, fallback to `quantity_delivered` on `entregado` status, or `-` when pending.
- **Unit abbreviation**:
  - Resolve with `$detail->product->unitOfMeasure?->abbreviation ?? $detail->product->unitOfMeasure?->name ?? 'UND'`.
- **Discrepancy & note resolution**:
  - Evaluate discrepancy via `abs($recvQty - $reqQty) >= 0.01` and display observation note via `$detail->receive_observation ?? $detail->observation`.

## Risks / Trade-offs

- **[Thermal printer layout wrapping]** → Numbers remain formatted to 2 decimals (`number_format($val, 2)`) within fixed table column widths (50%, 16%, 17%, 17%) to prevent line overflowing on 80mm roll width.
