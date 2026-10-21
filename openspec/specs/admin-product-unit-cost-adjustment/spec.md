# admin-product-unit-cost-adjustment

## Purpose

Proporcionar a los usuarios con rol Administrador la capacidad de ajustar el costo unitario de los productos desde la interfaz web, recalculando la valorización monetaria del inventario y del Kardex de manera consistente sin modificar el stock físico ni otros parámetros.

## Requirements

### Requirement: Acceso exclusivo a usuarios con rol administrador
El sistema DEBE permitir el acceso a la acción y endpoint de ajuste de costo unitario exclusivamente a usuarios autenticados que posean rol de administrador (`admin` o `Super Admin`). Cualquier otro usuario DEBE recibir un error de acceso no autorizado (HTTP 403) y no DEBE ver el botón de acción en la interfaz.

#### Scenario: Usuario administrador visualiza y ejecuta la acción
- **WHEN** un usuario con rol `admin` o `Super Admin` ingresa al módulo de productos
- **THEN** visualiza la acción para ajustar el costo unitario del producto y puede enviar el nuevo costo satisfactoriamente.

#### Scenario: Usuario no administrador intenta ajustar el costo
- **WHEN** un usuario con rol distinto a administrador navega por la lista de productos o intenta enviar una solicitud de ajuste de costo
- **THEN** la acción no está visible en la interfaz y cualquier solicitud enviada al endpoint responde con código de estado HTTP 403 Forbidden.

### Requirement: Validación del nuevo costo unitario
El sistema DEBE requerir un nuevo valor de costo unitario numérico, obligatorio, mayor o igual a cero y con precisión decimal.

#### Scenario: Valor de costo válido
- **WHEN** el administrador envía un valor numérico mayor o igual a 0 (ej. 45.5000)
- **THEN** el sistema acepta el valor y procede con el recálculo.

#### Scenario: Valor de costo negativo o no numérico
- **WHEN** el administrador intenta guardar un costo menor a cero o un texto no numérico
- **THEN** el sistema rechaza la operación con errores de validación legibles y no efectúa cambios.

### Requirement: Recálculo en cascada sin alterar el stock físico
El sistema DEBE preservar con exactitud matemática la cantidad física actual (`quantity`) en la tabla de existencias (`stocks`), actualizando únicamente `average_cost` con el nuevo costo y recalculando `inventory_value = quantity * average_cost`. Asimismo, DEBE reflejar este nuevo costo promedio y valor total en el Kardex y en el detalle de compra original sin alterar las unidades físicas registradas.

#### Scenario: Ajuste exitoso de costo unitario
- **WHEN** el administrador guarda el nuevo costo unitario para un producto que tiene 20 unidades físicas en stock
- **THEN** la cantidad en stock permanece exactamente en 20 unidades, el costo promedio pasa a ser el nuevo costo, el valor total del inventario pasa a ser `20 * nuevo_costo`, y los saldos del Kardex quedan perfectamente cuadrados y actualizados.
