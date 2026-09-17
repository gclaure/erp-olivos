## Context

En el sistema ERP de Olivos, la vista de creación de consumos `/admin/consumption-requests/create` es compartida por Consumidores (que piden insumos para cocina o áreas operativas) y por Administradores (que gestionan y pueden crear solicitudes directas).

Recientemente se abstrajo el stock al 100% para el rol Consumidor (siempre ve "Disponible" en verde sin cifras). Sin embargo, para el **Administrador**, el requerimiento es diferente:
1. El Administrador **debe ver siempre el dato real del stock** disponible y reservado (ej. `0` en rojo, reservas).
2. El Administrador **debe poder agregar insumos con stock cero** y guardar la solicitud sin bloqueos de interfaz ni rechazos del backend.

## Goals / Non-Goals

**Goals:**
- Permitir al rol Administrador interactuar con productos con stock 0 en modo consumo.
- Mantener visibles los badges numéricos de stock y reservas en tarjetas y modales para el Administrador.
- Mantener advertencias visuales informativas en el carrito pero habilitar el botón "Enviar Solicitud de Consumo".
- Permitir el guardado exitoso en `SaveConsumptionRequest.php` para usuarios con rol Administrador / Super Administrador.
- Preservar intacto el comportamiento transparente del rol Consumidor (badge verde sin cifras) y las restricciones de otros flujos.

**Non-Goals:**
- No se ocultarán los datos de stock al Administrador (debe ver cuántas existencias quedan).
- No se modificará el flujo de ventas ordinarias POS ni las reglas de Almacén.

## Decisions

### 1. Desbloqueo Condicional de Interacción en `ProductCard.vue`
- Se introduce `isAdmin` en `ProductCard.vue`:
  ```javascript
  const isAdmin = computed(() => {
      const user = page.props.auth?.user;
      if (!user) return false;
      if (user.is_super_admin) return true;
      const roles = user.roles || [];
      return roles.some(r => ['Admin', 'admin', 'Administrador', 'administrador', 'Super Admin'].includes(r));
  });
  ```
- `canInteract` se actualiza a:
  ```javascript
  const canInteract = computed(() => isConsumer.value || (isConsumptionMode.value && isAdmin.value) || hasStock.value);
  ```
- `maxQty` se actualiza a `99999` si es Consumidor o si es Administrador en consumo:
  ```javascript
  const maxQty = computed(() => (isSupply.value || isConsumer.value || (isConsumptionMode.value && isAdmin.value)) ? 99999 : Math.max(1, Math.floor(availableQty.value)));
  ```
- **Preservación visual**: El badge numérico de stock (`Math.floor(availableQty)`) y de reservas (`Res: X`) se mantiene para el Administrador.

### 2. Habilitación en `ProductDetailModal.vue`
- `canAdd` y `maxQty` adoptan la misma condición `isConsumer.value || (isConsumptionMode.value && isAdmin.value) || hasStock.value`.
- El banner de disponibilidad sigue indicando el saldo físico real (`Stock Disponible: X uds`), permitiendo al Administrador ver la escasez pero agregando el ítem al pedido.

### 3. Información sin Bloqueo en `CartSidebar.vue`
- `hasStockErrors` devolverá `false` si es modo consumo y el usuario es Consumidor o Administrador:
  ```javascript
  const hasStockErrors = computed(() => {
      if (props.operationType === 'consumption' && (isConsumer.value || isAdmin.value)) {
          return false;
      }
      return props.cart.some(item => isStockExceeded(item));
  });
  ```
- Se mantiene el aviso visual `Stock insuficiente: X disp.` en cada ítem como advertencia informativa para el Administrador.

### 4. Autorización en Backend (`SaveConsumptionRequest.php`)
- En `withValidator`, se exime de la validación restrictiva de `$requestedQty > $availableQty` si el usuario es Consumidor O Administrador / Super Administrador:
  ```php
  $isAuthorizedToBypassStock = $isConsumer || ($user && ($user->is_super_admin || $user->hasRole(['Admin', 'admin', 'Administrador', 'administrador', 'Super Admin', 'super-admin'])));
  if ($isAuthorizedToBypassStock) {
      return;
  }
  ```

## Risks / Trade-offs

- **[Riesgo]** Confusión entre el rol Consumidor y Administrador.  
  → **Mitigación**: La lógica evalúa explícitamente `isConsumer` (sin privilegios de admin) para ocultar stock, mientras que `isAdmin` mantiene los datos visibles.
