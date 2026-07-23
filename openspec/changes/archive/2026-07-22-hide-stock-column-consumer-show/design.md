## Design

### Approach

Add `v-if="!isConsumidorRole"` to the Stock Físico column header and corresponding data cells in `Show.vue`.

### Files Modified

- `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`

### Component Details

**Desktop table (md:block):**
- `<th>` at line ~697: add `v-if="!isConsumidorRole"`
- `<td>` at line ~877-881: add `v-if="!isConsumidorRole"`

**Mobile card view:**
- Grid cell at lines ~1046-1051: add `v-if="!isConsumidorRole"`

### Constraints

- `isConsumidorRole` is already defined at line 22-25 of Show.vue
- No backend changes needed
- No API changes
