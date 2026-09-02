## Context

En `Show.vue`, la tabla de detalle muestra una columna `ESTADO` para cada producto. Cuando almacén despacha el pedido, `item.quantity_delivered >= item.quantity_requested` activa un badge `🚚 DESPACHADO`. Para el consumidor, este texto causa confusión ya que se encuentra en plena recepción física de los insumos.

## Goals / Non-Goals

**Goals:**
- Presentar badges contextuales según el rol y la fase operativa:
  - Consumidor en fase de recepción (`despachado` o `despachado_parcial` sin recepcionar):
    - Si el input de recepción coincide: `📦 POR CONFIRMAR` (badge índigo/azul).
    - Si el input de recepción difiere de lo solicitado: `⚠️ CON DIFERENCIA (${receivedQuantities[item.id]} / ${item.quantity_requested})` (badge ámbar/naranja).
  - Estado entregado (`entregado`):
    - Si `quantity_received == quantity_requested`: `✅ ENTREGADO / RECIBIDO` (badge verde).
    - Si hubo diferencia (`quantity_received != quantity_requested`): `⚠️ RECIBIDO PARCIAL` (badge ámbar).
  - Vista Almacén / Admin: mantiene la indicación `🚚 DESPACHADO` o `ENTREGADO` según corresponda.
- Agregar un banner de guía superior cuando el consumidor tiene una solicitud pendiente de confirmación de recepción.

## Decisions

### 1. Helper Computado para Badge de Ítem en `Show.vue`
Crear `getItemStatusBadge(item)` para unificar las condiciones tanto en la vista de escritorio como en las cards móviles.

### 2. Banner de Instrucción para Consumidor
Mostrar un banner superior con fondo índigo/azul suave indicando que Almacén ya preparó y envió los productos, e invitando a verificar los inputs y presionar `[ Confirmar Recepción ]`.
