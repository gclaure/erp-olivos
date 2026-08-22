## Context

En el módulo de compras (`/admin/purchases/create`), el buscador asíncrono consulta el endpoint `admin.api.selects.products`. Actualmente este endpoint no discrimina el tipo de producto (`materia_prima` vs `insumo`) y no calcula el stock específico del almacén seleccionado. Dado que los insumos no manejan stock ni generan registros en Kardex, el usuario ha solicitado que en compras únicamente se puedan buscar materias primas y que el buscador exija la selección previa del almacén para consultar el stock disponible exacto en ese almacén.

## Goals / Non-Goals

**Goals:**
- Deshabilitar el input del buscador de productos en compras cuando `!form.warehouse_id`.
- Filtrar la consulta de productos en compras para retornar únicamente productos con `type = 'materia_prima'`.
- Extender `ApiSelectController@products` para recibir `warehouse_id` y `type`, calculando `stock` mediante `withSum(['stocks as warehouse_stock' => ...])`.
- Mostrar en cada fila del dropdown de resultados el stock disponible en el almacén seleccionado (`Stock: XX UND`) con indicador visual de agotado o disponible.

**Non-Goals:**
- No alterar otros endpoints de búsqueda que requieran todos los productos si no envían el parámetro `type`.
- No modificar el proceso de guardado o cálculo de totales de compra ya validado en `PurchaseService`.

## Decisions

1. **Parámetro `type` en `ApiSelectController@products`**:
   - Agregar `when($request->type, fn($q, $t) => $q->where('type', $t))`.
   - En `Purchase/Create.vue`, enviar `params: { search: query, warehouse_id: form.warehouse_id, type: 'materia_prima' }`.
   - *Razón*: Mantiene el endpoint genérico y reutilizable pero permite a compras forzar exclusivamente materias primas.

2. **Cálculo de Stock en `ApiSelectController`**:
   - Usar `withSum` con closure que filtre por `warehouse_id` si está presente:
     ```php
     ->withSum(['stocks as warehouse_stock' => function($q) use ($warehouseId) {
         if ($warehouseId) {
             $q->where('warehouse_id', $warehouseId);
         }
     }], 'quantity')
     ```
   - Mapear `'stock' => (float)($p->warehouse_stock ?? 0)`.

3. **UX del Input Deshabilitado**:
   - `disabled` nativo en el `<input>`.
   - Clases condicionales de Tailwind: `opacity-60 cursor-not-allowed bg-zinc-100 dark:bg-zinc-800`.
   - Placeholder dinámico: `"Seleccione primero un almacén de destino..."` vs `"Escriba el nombre o código para buscar..."`.

## Risks / Trade-offs

- **[Riesgo]** El usuario cambia de almacén después de haber buscado productos.
  - **Mitigación**: Si el usuario cambia el almacén en el paso 1, los detalles ya agregados se mantienen (o se actualiza su validación) y futuras búsquedas consultan automáticamente el nuevo almacén.
