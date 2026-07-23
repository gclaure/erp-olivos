# Plan: hide-stock-column-consumer-show

**OpenSpec change name:** `hide-stock-column-consumer-show`  
**Scaffold:** `openspec/changes/hide-stock-column-consumer-show/` (creado; artifacts pendientes por plan mode)

## Objetivo

En `/admin/consumption-requests/{id}`, el rol **Consumidor** no debe ver nunca la columna/bloque **Stock Físico** en **Insumos Solicitados** (desktop + móvil). Admin/Almacén sin cambios. Botón **Despachar Stock** no se toca.

## Contexto técnico

- Archivo único: `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`
- Ya existe `isConsumidorRole` (roles Consumidor/consumidor)
- Stock Físico hoy siempre visible:
  - Desktop th ~L697
  - Desktop td ~L877–880 (`getRemainingStock(item)`)
  - Mobile bloque ~L1046–1051
- Admin ve Despachar vía `canUserDispatch` — fuera de alcance

## Alcance

**In:**
- `v-if="!isConsumidorRole"` en th/td Stock Físico y bloque móvil equivalente

**Out:**
- Badges Estado (DISPONIBLE / STOCK PARCIAL / SIN STOCK) — follow-up opcional
- Backend / resource / payload
- Botón Despachar Stock

## OpenSpec artifacts (al salir de plan mode /opsx-propose o apply prep)

### proposal.md — Why / What / Capabilities / Impact

- **Why:** Consumidor no debe ver cantidades de inventario en el detalle (alineado al catálogo create).
- **What:** Ocultar Stock Físico desktop+móvil para Consumidor; resto de roles igual.
- **Capabilities modified:** `consumer-consumption-catalog-ui` (ADDED req show detail).
- **Impact:** solo `Show.vue`.

### design.md

- Gate UI con `isConsumidorRole`; sin cambios API.
- No strip de `stock_available` del payload (Admin lo necesita vía misma página/resource).
- Grid móvil: al quitar Stock Físico, ajustar a 2 cols o reordenar Solicitado / Despachado.

### specs delta — `specs/consumer-consumption-catalog-ui/spec.md`

```markdown
## ADDED Requirements

### Requirement: Consumidor no ve Stock Físico en detalle de solicitud
En Show de consumption request, el sistema SHALL ocultar la columna/bloque Stock Físico al rol Consumidor (desktop y móvil).

#### Scenario: Consumidor en Show
- WHEN Consumidor abre /admin/consumption-requests/{id}
- THEN MUST NOT ver encabezado ni valores de Stock Físico

#### Scenario: Admin en Show
- WHEN Admin/Almacén abre el mismo detalle
- THEN MUST seguir viendo Stock Físico
```

### tasks.md

1. Desktop: `v-if="!isConsumidorRole"` en th y td Stock Físico  
2. Móvil: ocultar bloque Stock Físico; ajustar grid  
3. Verificar Consumidor sin stock; Admin con stock; Despachar intacto  

## Implementación (cuando se ejecute)

```vue
<th v-if="!isConsumidorRole" ...>Stock Físico</th>
<td v-if="!isConsumidorRole" ...>...</td>
<!-- mobile: v-if="!isConsumidorRole" en el div del stock -->
```

## Verificación manual

1. Login Consumidor → Show → sin “Stock Físico”  
2. Login Admin → Show → con stock + Despachar Stock  
3. Mobile Consumidor → sin bloque stock  

## Next steps

1. Salir de plan mode (o aprobar este plan para ejecución)  
2. Completar artifacts OpenSpec en `openspec/changes/hide-stock-column-consumer-show/`  
3. `/opsx-apply` → implementar en `Show.vue`  
4. `/opsx-archive` + sync specs  
