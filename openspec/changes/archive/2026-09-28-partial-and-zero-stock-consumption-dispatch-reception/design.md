# Design

## Context

En el ERP Olivos, el ciclo de solicitudes de consumo interno involucra a Consumidor (crea y recepciona), Administrador (aprueba o crea con bypass de stock) y Almacén (prepara y despacha). Ver `proposal.md` para la motivación.

Actualmente, cuando un ítem no tiene stock suficiente o tiene stock 0:
1. Almacén debe tener visibilidad clara en todo momento de las existencias y los faltantes.
2. Cocina (Consumidor) debe poder confirmar la recepción física de la entrega, registrando si un producto vino en 0 o parcial junto a su justificación ("Sin stock en almacén"), sin bloqueos de interfaz ni excepciones en el backend.

## Goals / Non-Goals

**Goals:**
- Mantener visible el banner de faltantes para Almacén y Administrador en estados `pendiente`, `aprobado`, `observado` y `despachado_parcial`.
- Permitir que Cocina ingrese cantidades recibidas y observaciones para **todos** los ítems de la solicitud, incluyendo aquellos con entrega 0.
- Garantizar que `handleReceive` en frontend y `receiveRequest` en backend procesen sin errores las entregas con ítems en cero o parciales con observación.
- Asegurar que el Kardex y stock solo se descuenten por las unidades efectivamente recibidas (`qty > 0`).

**Non-Goals:**
- No se altera la lógica de Kardex existente (el débito sigue ocurriendo estrictamente en la recepción conforme).
- No se modifica la generación automática de la Solicitud de Compra (`PurchaseOrder`) al despachar con faltantes.

## Decisions

### 1. Banner de faltantes en estado `despachado_parcial`
- **Problema:** La condición en `Show.vue` evaluaba `request.status === 'parcial'`, mientras que el backend usa `despachado_parcial`.
- **Decisión:** Ajustar la condición a:
  ```javascript
  v-if="!isConsumidorRole && !isFullyStocked && (request.status === 'pendiente' || request.status === 'aprobado' || request.status === 'observado' || request.status === 'despachado_parcial' || request.status === 'parcial')"
  ```

### 2. Habilitación de inputs de recepción para todos los ítems en Cocina
- **Problema:** `Show.vue` tenía `v-if="item.quantity_delivered > 0"` tanto en la tabla desktop (línea 1491) como en la vista móvil (línea 1682). Los productos con entrega 0 mostraban un guión `—` y no permitían ingresar recepción ni observación.
- **Decisión:** Retirar la condición limitante `item.quantity_delivered > 0` en el bloque de recepción de Cocina:
  ```html
  <div v-if="isConsumidorRole && (request.status === 'despachado' || request.status === 'despachado_parcial')">
      <!-- Muestra input numérico precargado con item.quantity_delivered (ej. 0 o parcial) -->
      <!-- Muestra textarea de observación obligatoria cuando hay discrepancia con quantity_requested -->
  </div>
  ```
- **Inicialización:** En `initQuantities`, precargar `receivedQuantities.value[item.id] = parseFloat((item.quantity_delivered || 0).toFixed(2))`.

### 3. Iteración completa en `handleReceive`
- **Problema:** `handleReceive` filtraba `if (item.quantity_delivered > 0)`, omitiendo ítems en cero del payload de recepción.
- **Decisión:** Iterar sobre todos los ítems en `request.value.details`, permitiendo enviar `0` y exigiendo la observación de al menos 3 caracteres por faltante.

### 4. Robustez en `receiveRequest` Backend
- `ConsumptionRequestService::receiveRequest` ya valida la presencia de observaciones si hay discrepancia. Al recibir el payload completo desde el frontend (incluyendo los ítems con 0 y su motivo), la transacción se completa con éxito sin lanzar excepciones y actualiza el estado a `entregado`.

## Risks / Trade-offs

- **[Riesgo]** Que el usuario de cocina omita la observación cuando un producto viene en cero.
  - **Mitigación:** La validación en frontend (`handleReceive`) y backend (`receiveRequest`) exige estrictamente una observación explicativa (ej. "Sin stock en almacén").
