# Spec Delta

## Purpose

Permite gestionar el registro, consulta y anulación de pedidos y reservas de clientes como órdenes técnicas y logísticas, desacopladas de cobros y transacciones monetarias.

## ADDED Requirements

### Requirement: Registro de pedido puramente operacional
El sistema DEBE permitir registrar reservas de clientes capturando únicamente datos de contacto, fecha y hora de entrega, modalidad y dirección, productos personalizados con sus especificaciones, fotografías en formato WebP y complementos adicionales, sin requerir ni procesar totales monetarios ni abonos económicos.

#### Scenario: Registro exitoso de reserva técnica
- **WHEN** el usuario completa los datos del cliente, fecha/hora de entrega y al menos un producto personalizado con sus detalles y guarda el formulario sin ingresar montos monetarios
- **THEN** el sistema registra el pedido con estado inicial `registrado`, asigna el número secuencial formateado y redirecciona al historial sin exigir campos económicos

#### Scenario: Exclusión de campos económicos en el registro
- **WHEN** el usuario visualiza el formulario de creación de pedidos
- **THEN** no debe mostrarse ningún campo para subtotal, descuento, abonos de pago, métodos de pago ni cálculo de saldos pendientes

### Requirement: Anulación de pedidos
El sistema DEBE proporcionar la capacidad de anular un pedido registrado previamente, solicitando confirmación y registrando la fecha de anulación, el usuario que anula y el motivo de la anulación.

#### Scenario: Anulación confirmada de un pedido activo
- **WHEN** un usuario autenticado selecciona la acción de anular un pedido en estado `registrado` e ingresa un motivo opcional
- **THEN** el sistema actualiza el estado del pedido a `anulado`, registra la fecha y hora actual, asocia el ID del usuario y muestra un mensaje de confirmación

#### Scenario: Protección contra anulación duplicada
- **WHEN** se consulta o intenta anular un pedido que ya se encuentra en estado `anulado`
- **THEN** el sistema no permite volver a anularlo y la acción no está disponible para dicho registro

### Requirement: Historial, filtrado y comanda técnica
El sistema DEBE permitir consultar el historial de pedidos mostrando claramente el estado operativo (`registrado` o `anulado`), filtrando por estado, fechas, cliente o número de reserva, y generando comandas técnicas en PDF sin desglose económico.

#### Scenario: Filtrado por estado en el historial
- **WHEN** el usuario filtra el historial seleccionando el estado `anulado`
- **THEN** el sistema lista únicamente los pedidos que han sido anulados, mostrando sus etiquetas visuales distintivas y motivos

#### Scenario: Emisión de comanda técnica en PDF
- **WHEN** el usuario solicita imprimir o descargar el comprobante PDF de un pedido
- **THEN** el documento generado muestra los datos del cliente, entrega, productos, especificaciones técnicas, complementos y estado actual, sin mostrar totales, pagos ni saldos
