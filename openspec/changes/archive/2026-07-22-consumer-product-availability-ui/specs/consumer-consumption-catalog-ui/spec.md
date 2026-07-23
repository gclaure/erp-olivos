## ADDED Requirements

### Requirement: Consumidor ve disponibilidad semántica sin cantidad de stock

En la vista de creación de solicitudes de consumo (`/admin/consumption-requests/create`, catálogo POS en modo consumption), el sistema SHALL mostrar a usuarios con rol Consumidor el estado de cada producto como disponible o no disponible, sin exponer la cantidad numérica de stock en la tarjeta del producto ni en su tooltip.

#### Scenario: Producto con stock neto positivo

- **WHEN** un usuario con rol Consumidor visualiza el catálogo de productos en modo consumo
- **AND** el stock físico del almacén activo menos las reservas totales del producto es mayor que cero
- **THEN** la tarjeta MUST mostrar el estado “Disponible” con indicador visual verde
- **AND** MUST NOT mostrar el número de unidades de stock en la tarjeta ni en el tooltip

#### Scenario: Producto sin stock neto

- **WHEN** un usuario con rol Consumidor visualiza el catálogo de productos en modo consumo
- **AND** el stock físico del almacén activo menos las reservas totales del producto es cero o menor
- **THEN** la tarjeta MUST mostrar el estado “No disponible” con indicador visual rojo
- **AND** el producto MUST permanecer no seleccionable (mismo comportamiento de bloqueo actual)
- **AND** MUST NOT mostrar el número de unidades de stock

#### Scenario: Admin u otro rol en la misma pantalla

- **WHEN** un usuario sin rol Consumidor (p. ej. Admin) visualiza el catálogo en modo consumo
- **THEN** la tarjeta MUST conservar la presentación numérica de stock disponible y el badge de reservas globales actuales

### Requirement: Consumidor ve solo sus propias reservas como “Mis reservados”

En el catálogo de productos en modo consumo, para usuarios con rol Consumidor, el sistema SHALL indicar la cantidad reservada pendiente atribuible únicamente al usuario autenticado, con la etiqueta “Mis reservados: N”.

#### Scenario: Usuario con reservas propias pendientes

- **WHEN** un Consumidor visualiza un producto para el cual tiene cantidad pendiente (solicitada menos entregada) en solicitudes de consumo del almacén activo en estados que reservan stock (`pendiente`, `parcial`, `despachado_parcial`, `compras_generado`)
- **THEN** la tarjeta MUST mostrar el badge con el texto “Mis reservados: N” donde N es esa cantidad (entera, floor) del usuario autenticado
- **AND** N MUST NOT incluir reservas de otros usuarios ni reservas de ventas

#### Scenario: Usuario sin reservas propias

- **WHEN** un Consumidor visualiza un producto sin cantidad pendiente propia en esos estados
- **THEN** la tarjeta MUST NOT mostrar el badge “Mis reservados”

#### Scenario: Disponibilidad no se calcula solo con reservas propias

- **WHEN** el sistema determina si un producto está disponible para un Consumidor
- **THEN** MUST restar las reservas totales del almacén (ventas no entregadas + consumo pendiente global), no únicamente `my_reserved_quantity` del usuario

### Requirement: API de productos POS expone reservas del usuario autenticado

La búsqueda de productos del POS SHALL incluir un campo numérico con las reservas de consumo pendientes del usuario autenticado por producto y almacén, sin cambiar la semántica del campo de reservas totales existente.

#### Scenario: Respuesta incluye my_reserved_quantity

- **WHEN** un usuario autenticado solicita la búsqueda de productos del POS para un almacén
- **THEN** cada producto en la respuesta MUST incluir `my_reserved_quantity` como número ≥ 0 correspondiente a sus solicitudes de consumo pendientes en ese almacén
- **AND** `reserved_quantity` MUST seguir representando la suma de reservas globales usadas para disponibilidad

#### Scenario: Usuario sin solicitudes pendientes

- **WHEN** el usuario autenticado no tiene detalle pendiente de consumo para un producto en el almacén
- **THEN** `my_reserved_quantity` MUST ser 0
