## Why

En el flujo de solicitudes de consumo interno (creación y edición), los usuarios operativos (Cocina, Pastelería, etc.) solicitan los insumos que necesitan para su producción diaria. Actualmente, la interfaz POS reutiliza la lógica de control de ventas donde tener stock disponible = 0 pinta la tarjeta con borde rojo de error (`⚠️ STOCK INSUFICIENTE: 0 DISP.`) y bloquea el botón de envío ("Corrija el stock excedido") o bloquea la adición en la modal. Esto es un error conceptual porque las solicitudes de consumo no son ventas: se solicitan precisamente para que almacén despache lo existente y genere compras o despachos parciales para los faltantes.

## What Changes

- Eliminar el bloqueo de stock insuficiente en el carrito lateral (`CartSidebar.vue`) cuando el tipo de operación es `consumption`. Los usuarios pueden solicitar cualquier cantidad requerida sin que se les impida enviar o guardar la solicitud.
- Remover el borde rojo de error invasivo y el mensaje de bloqueo en el carrito de solicitudes de consumo. Mostrar únicamente el stock físico disponible como dato informativo.
- Permitir en la modal de detalle del producto (`ProductDetailModal.vue`) agregar el producto a la solicitud incluso si el stock actual en almacén es 0, habilitando el botón `[ Agregar a la Solicitud ]` en modo `consumption`.
- Corregir en `ConsumptionRequestController::edit()` el payload de `cartItems` para que incluya la relación de `stocks` completa con las cantidades disponibles por almacén.

## Capabilities

### New Capabilities
- `consumption-unrestricted-stock-request`: Eliminación de bloqueos de stock en solicitudes de consumo interno para permitir pedidos de insumos con stock cero o faltante.

### Modified Capabilities

## Impact

- Frontend: `CartSidebar.vue`, `ProductDetailModal.vue`, `ProductCard.vue`.
- Backend: `ConsumptionRequestController.php` (`edit` method payload).
