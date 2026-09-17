## Why

En la vista de creación de consumos (`/admin/consumption-requests/create`), los usuarios con rol **Administrador** y **Super Administrador** necesitan tener la facultad de registrar solicitudes de insumos incluso cuando el stock físico disponible sea cero (`0`), debido a requerimientos de planificación operativa o pedidos urgentes que luego serán abastecidos por almacén o compras. 

A diferencia del rol Consumidor (quien no ve números de stock ni reservas y solo visualiza la etiqueta "Disponible"), el Administrador **debe mantener la visibilidad completa del dato de stock real** (número de existencias disponibles y reservas) tanto en las tarjetas del catálogo como en los modales y el carrito, pero con la capacidad de agregarlos y confirmar la solicitud sin bloqueos.

## What Changes

- **Habilitación de interacción en tarjetas (`ProductCard.vue`)**: Para el rol Administrador en modo consumo, las tarjetas no tendrán opacidad reducida (`opacity-60`) ni cursor deshabilitado cuando el stock sea 0, permitiendo interactuar y seleccionar cantidades libremente, manteniendo el badge numérico real de stock (`0` en rojo o positivo en verde) y las reservas (`Res: X`).
- **Desbloqueo en Modal de Detalle (`ProductDetailModal.vue`)**: El Administrador seguirá viendo la información detallada de existencias reales (`Stock Disponible: 0 uds` y reservas), pero el selector de cantidad y el botón "Agregar a la Solicitud" estarán habilitados.
- **Validación visual y de envío en Carrito (`CartSidebar.vue`)**: Se mantiene el aviso informativo de stock insuficiente en cada ítem para el Administrador, pero la propiedad `hasStockErrors` no bloqueará el botón principal "Enviar Solicitud de Consumo".
- **Bypass de validación en Backend (`SaveConsumptionRequest.php`)**: El método `withValidator` permitirá guardar la solicitud de consumo cuando el usuario autenticado sea Administrador o Super Administrador, evitando el rechazo por `$requestedQty > $availableQty`.

## Capabilities

### New Capabilities
- `admin-zero-stock-consumption`: Permite al usuario con rol Administrador solicitar insumos con stock cero en modo consumo mientras preserva la visibilidad de los datos de inventario.

### Modified Capabilities
<!-- No modified capabilities -->

## Impact

- **Frontend**: `ProductCard.vue`, `ProductDetailModal.vue` y `CartSidebar.vue`.
- **Backend**: `app/Http/Requests/Admin/SaveConsumptionRequest.php`.
- **Segregación de roles**: El rol Consumidor mantiene su diseño transparente (badge verde sin cifras); el rol Administrador ve el dato real de stock pero puede operar sin restricciones.
