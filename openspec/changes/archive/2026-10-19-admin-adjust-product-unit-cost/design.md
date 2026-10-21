# Design

## Context

El sistema de inventario utiliza **Costo Promedio Ponderado** (Weighted Average Cost). El estado de existencias y valorización reside en tres entidades:
1. `stocks` (`quantity`, `average_cost`, `inventory_value`).
2. `kardexes` (`quantity`, `unit_cost`, `total_cost`, `balance_quantity`, `balance_total_cost`, `avg_cost`).
3. `purchase_details` y `purchases` (comprobante origen de la entrada valorizada).

Actualmente los productos fueron ingresados con una única compra inicial masiva y no tienen ventas ni consumos. El usuario necesita corregir el costo unitario de los productos de forma puntual desde la interfaz web, sin modificar las cantidades de stock físico ni ningún otro atributo del producto.

## Goals / Non-Goals

**Goals:**
- Proporcionar un botón y modal responsivo en `resources/js/Pages/Admin/Product/Index.vue` visible exclusivamente para usuarios administradores (`is_super_admin` o rol `Admin`/`Administrador`).
- Implementar un Form Request (`AdjustProductCostRequest`) con autorización estricta y validación numérica.
- Implementar un servicio de negocio `ProductCostAdjustmentService` con transacción atómica en base de datos.
- Mantener intacto el valor de `stocks.quantity` y el campo `quantity` en Kardex y compras.
- Recalcular `stocks.average_cost = new_cost` e `stocks.inventory_value = stocks.quantity * new_cost`.
- Actualizar los registros de Kardex asociados al producto para que `unit_cost`, `avg_cost`, `total_cost` y `balance_total_cost` cuadren matemáticamente con el nuevo costo.
- Si existe una compra de apertura/inicial para el producto, actualizar `purchase_details.unit_price`, `purchase_details.subtotal` y el `purchases.total` correspondiente para consistencia documental.

**Non-Goals:**
- No alterar cantidades físicas de inventario.
- No modificar precios de venta al público (`products.price`).
- No alterar nombres, códigos, categorías ni empaques.
- No permitir acceso a roles operativos (mesero, cocina, bodega no-admin).

## Decisions

### Decisión 1: Endpoint dedicado vs Endpoint genérico de actualización de producto
- **Elección:** Crear un endpoint y acción dedicada `POST /admin/products/{product}/adjust-cost` atendido por un servicio específico `ProductCostAdjustmentService`.
- **Razón:** La actualización habitual de productos (`PUT /admin/products/{product}`) modifica atributos maestros (nombre, unidad, categorías, imagen) pero no recalcula la valorización financiera ni el Kardex. Separar la responsabilidad (SRP) previene mutaciones accidentales de inventario y permite aplicar permisos de auditoría específicos.
- **Alternativa descartada:** Añadir un campo editable `unit_cost` en el formulario regular de edición de producto. Descartado porque violaría las reglas de integridad ERP donde el costo es un valor contable transaccional, no un simple atributo maestro.

### Decisión 2: Sincronización en Cascada Atómica (DB Transaction)
- **Elección:** Encapsular la actualización de `stocks`, `kardexes` y `purchase_details` dentro de una transacción `DB::transaction()` con bloqueo de fila (`lockForUpdate()`) en `Stock`.
- **Razón:** Prevenir condiciones de carrera y garantizar que la ecuación contable `Valor Inventario = Stock Físico × Costo Promedio` se cumpla sin discrepancias.
- **Alternativa descartada:** Modificar únicamente la tabla `stocks`. Descartado porque el reporte de Kardex físico-valorado exportable quedaría descuadrado con respecto al inventario de almacén.

### Decisión 3: Control de visualización en Frontend y Backend
- **Elección:** En el backend, validar con `$request->user()->is_super_admin || $request->user()->hasRole(['Admin', 'Administrador', 'admin', 'administrador'])`. En el frontend, verificar `auth.user.is_super_admin || auth.user.roles?.some(r => ['admin', 'administrador'].includes(r.name.toLowerCase()))`.
- **Razón:** Consistencia con las políticas de seguridad existentes en `HandleInertiaRequests.php`.

## Risks / Trade-offs

- **[Riesgo]** Múltiples registros de compra históricos en el futuro.
  - **Mitigación:** El servicio actualiza el registro de apertura/compra inicial directa y el stock actual. En caso de productos con transacciones más complejas, se ajusta el stock actual y el último saldo de Kardex, dejando una nota de auditoría.
- **[Riesgo]** Valores decimales con error de coma flotante.
  - **Mitigación:** Uso estricto de la librería `Brick\Math\BigDecimal` y redondeo `HALF_UP` a 4 decimales para costos unitarios y 2 decimales para totales monetarios, tal como estipula el estándar del proyecto.
