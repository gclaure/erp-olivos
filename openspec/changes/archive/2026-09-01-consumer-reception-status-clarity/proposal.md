## Why

Cuando un pedido de consumo interno ha sido despachado por el almacén (`status === 'despachado'` o `despachado_parcial`), el usuario consumidor entra a la vista de detalle para verificar las cantidades recibidas. En la tabla de productos, la columna "ESTADO" muestra un badge fucsia `🚚 DESPACHADO` en cada producto, lo que confunde al usuario haciéndole pensar que el proceso ya finalizó o que no refleja su acción de recepción en curso.

## What Changes

- Mejorar los estados de línea de producto en la tabla de detalle (`Show.vue`):
  - Cuando la solicitud está en estado `despachado` o `despachado_parcial` y el usuario es el consumidor que está recepcionando:
    - Si aún no ha confirmado la recepción, mostrar un badge claro: `📦 POR CONFIRMAR RECEPCIÓN` (azul / ámbar suave).
    - Si el consumidor escribió una cantidad menor en el input (discrepancia en vivo): mostrar dinámicamente el badge de estado `⚠️ RECIBIENDO (X de Y)` con indicador de diferencia.
  - Una vez confirmada la recepción con el botón `[ Confirmar Recepción ]` (`status === 'entregado'`), el badge cambia a verde `✅ RECIBIDO / ENTREGADO` (o `⚠️ ENTREGADO CON DISCREPANCIA` si hubo diferencias registradas).
- Agregar un banner informativo contextual superior visible para el consumidor cuando la solicitud está despachada:
  *«Almacén ha despachado tus insumos. Verifica las cantidades recibidas en la tabla y presiona el botón [Confirmar Recepción] para finalizar el ciclo.»*

## Capabilities

### New Capabilities
- `consumer-reception-status-clarity`: Claridad en los estados de línea de producto y retroalimentación interactiva durante la fase de recepción del consumidor.

### Modified Capabilities

## Impact

- Frontend: `resources/js/Pages/Admin/ConsumptionRequest/Show.vue`.
