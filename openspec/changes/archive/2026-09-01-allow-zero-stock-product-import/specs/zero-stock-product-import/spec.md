## ADDED Requirements

### Requirement: Validación de productos con stock cero
El sistema DEBE permitir que las filas del archivo de importación tengan una cantidad igual a 0 (`cantidad = 0`). Solo se rechazarán cantidades estrictamente negativas (`cantidad < 0`) o con formato no numérico.

#### Scenario: Importación de fila con cantidad cero y costo unitario
- **WHEN** el usuario sube un archivo Excel con un producto que tiene `cantidad = 0` y `costo_unitario = 15.50`
- **THEN** el validador marca la fila como válida y permite proceder con la previsualización

#### Scenario: Importación de fila con cantidad cero y costo unitario vacío o cero
- **WHEN** el usuario sube un archivo Excel con un producto que tiene `cantidad = 0` y `costo_unitario` en blanco o `0`
- **THEN** el validador marca la fila como válida asignando costo 0.00 sin exigir un valor positivo

#### Scenario: Rechazo de fila con cantidad negativa
- **WHEN** el usuario sube un archivo Excel con un producto que tiene `cantidad = -5`
- **THEN** el validador rechaza la fila con el mensaje "Cantidad debe ser un número mayor o igual a 0."

### Requirement: Flexibilidad de fecha de compra según cantidad
La columna `fecha_compra` DEBE ser opcional si la cantidad es 0. Si la cantidad es estrictamente mayor a 0 (`cantidad > 0`), la `fecha_compra` DEBE ser obligatoria y no futura.

#### Scenario: Fila con cantidad cero y fecha de compra vacía
- **WHEN** el usuario importa un producto con `cantidad = 0` y `fecha_compra` en blanco
- **THEN** el sistema valida la fila como exitosa sin exigir fecha de compra

#### Scenario: Fila con cantidad mayor a cero y fecha de compra vacía
- **WHEN** el usuario importa un producto con `cantidad = 10` y `fecha_compra` en blanco
- **THEN** el sistema reporta error indicando que la fecha de compra es requerida para ingresos de stock

### Requirement: Registro de catálogo sin compra ni kardex para stock cero
Al ejecutar la importación, los productos con `cantidad = 0` DEBEN ser creados o actualizados en la base de datos (catálogo), pero NO DEBEN registrar detalle de compra (`purchase_details`), movimiento de stock, ni transacciones en el Kardex.

#### Scenario: Archivo con mezcla de productos con stock cero y con stock positivo
- **WHEN** se procesa un archivo con 2 productos de cantidad 0 y 3 productos con cantidad mayor a 0
- **THEN** los 5 productos son creados/actualizados en el catálogo, pero la orden de compra generada y el Kardex solo contienen los 3 productos con cantidad mayor a 0

#### Scenario: Archivo donde todos los productos tienen stock cero
- **WHEN** se procesa un archivo donde todos los productos tienen `cantidad = 0`
- **THEN** todos los productos se crean/actualizan en el catálogo y no se genera ningún registro en la tabla `purchases` ni en `kardex`
