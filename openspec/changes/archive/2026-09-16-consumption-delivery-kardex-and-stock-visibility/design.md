## Context

El flujo actual de consumo procesaba la salida en Kardex y el decremento de `stocks` en el paso de Despacho (`ConsumptionRequestDispatchService::dispatch`). Si la solicitud no llegaba a entregarse, los saldos quedaban desfasados en el Kardex. Adicionalmente, las tarjetas de productos en el catálogo (`ProductCard.vue`) bloqueaban la interacción para productos con stock cero (`0`), impidiendo que los consumidores soliciten materias primas o insumos que requieren reabastecimiento. Por último, en el listado de solicitudes (`Index.vue`) el almacenero no contaba con indicadores visuales de disponibilidad inmediata de stock.

## Goals / Non-Goals

**Goals:**
- Mover la lógica de decremento de `stocks` y registro en `kardex` (`KardexMovementType::ADJUSTMENT_OUT`) al método `ConsumptionRequestService::receiveRequest`.
- Mantener en `ConsumptionRequestDispatchService::dispatch` la preparación física de ítems, cálculo de faltantes y generación automática de la Solicitud de Compra (`PurchaseOrder`).
- Desbloquear en `ProductCard.vue` las tarjetas de productos para el modo consumo (`operationType === 'consumption'`), permitiendo agregar productos con stock `0` y mostrando badges informativos en lugar de bloqueo.
- Agregar en el listado (`Index.vue`) y en el detalle (`Show.vue`) indicadores de disponibilidad (verde: stock completo, amarillo: stock parcial, rojo: sin stock) y el panel de insumos faltantes con botón a compras.

**Non-Goals:**
- Modificar el flujo de ventas POS o cotizaciones.
- Alterar la lógica de cálculo del costo promedio (`avg_cost`) en el `KardexService`.

## Decisions

- **1. Reubicación del Kardex a `receiveRequest`**:
  - En `ConsumptionRequestDispatchService::dispatch`: solo se actualiza `quantity_delivered`, el estado de la solicitud (`despachado` o `despachado_parcial`) y se generan las órdenes de compra para faltantes.
  - En `ConsumptionRequestService::receiveRequest`: al confirmar la entrega, para cada detalle con `quantity_received > 0` (o `quantity_delivered > 0` si aplica) que sea inventoriable (`$isInventoriable`), se invoca `KardexService::record(type: KardexMovementType::ADJUSTMENT_OUT, ...)`, descontando efectivamente el stock en almacén.
- **2. Desbloqueo del catálogo en `ProductCard.vue`**:
  - Definir `const isConsumption = computed(() => props.operationType === 'consumption');`.
  - En modo consumo, `hasStock` se considerará siempre `true` para efectos de permitir el clic, abrir el modal o incrementar el stepper.
  - El badge superior en la imagen mostrará el stock real (o "Sin Stock / Bajo Pedido" en tono informativo) sin aplicar clases de opacidad (`opacity-60`) ni `cursor-not-allowed`.
- **3. Semáforos de disponibilidad de stock para Almacén**:
  - En el backend (`ConsumptionRequestResource` o query de `Index`), calcular para cada solicitud el porcentaje o estado de disponibilidad de sus ítems respecto al stock del almacén origen.
  - En `Index.vue`, renderizar badges visuales:
    * `100% Disponible` (Verde).
    * `Faltantes Parciales` (Ámbar).
    * `Sin Stock` (Rojo).
  - En `Show.vue`, mantener el panel de alerta superior que desglosa faltantes y ofrece el botón para generar o revisar la Solicitud de Compra.

## Risks / Trade-offs

- **[Cancelación de solicitudes despachadas pero no entregadas]** → Como el stock físico no ha sido debitado del Kardex, cancelar una solicitud antes de su entrega no requiere reversión contable en Kardex, simplificando el flujo y evitando entradas artificiales.
