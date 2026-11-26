# Spec Delta

## Purpose

Permite corregir el producto y la cantidad de un detalle de una solicitud de consumo ya aprobada mediante una solicitud de modificación iniciada por el almacenero y autorizada por el administrador, controlando las cantidades físicas preparadas, entregadas y devueltas, resolviendo las compras asociadas por línea y conservando un historial inmutable, sin reiniciar la aprobación de toda la solicitud.

## ADDED Requirements

### Requirement: Solicitud de modificación por el almacenero
El sistema SHALL permitir a un usuario de almacén registrar una solicitud de modificación sobre un detalle de una solicitud de consumo en estado `aprobado`, `despachado` o `despachado_parcial`, indicando el producto solicitado, la cantidad solicitada (mayor que cero) y un motivo obligatorio, sin alterar la solicitud original. Si solo cambia el producto, la cantidad solicitada SHALL conservar el valor original. El producto destino SHALL estar activo y tener la misma unidad de medida que el producto actual.

#### Scenario: Cambio solo de producto
- **WHEN** el almacenero selecciona el detalle "Mantequilla con sal" (2 unidades), elige "Mantequilla sin sal" sin editar la cantidad e ingresa el motivo "Producto seleccionado incorrectamente"
- **THEN** el sistema crea una modificación `pendiente` con cantidad original 2 y cantidad solicitada 2
- **AND** el detalle conserva el producto y la cantidad originales

#### Scenario: Cambio de producto y cantidad
- **WHEN** el almacenero solicita "Mantequilla sin sal" con cantidad 3 sobre un detalle original de 2 unidades
- **THEN** la modificación registra cantidad original 2 y cantidad solicitada 3

#### Scenario: Estado que no admite modificaciones
- **WHEN** el almacenero intenta solicitar una modificación sobre una solicitud `entregado`, `cancelado`, `pendiente` u `observado`
- **THEN** el sistema rechaza la operación indicando que el estado no admite modificaciones

#### Scenario: Datos inválidos
- **WHEN** el almacenero envía la solicitud sin motivo, con cantidad menor o igual a cero, con producto inactivo, con otra unidad de medida, o sin ningún cambio respecto al detalle actual
- **THEN** el sistema rechaza la operación indicando el motivo específico

#### Scenario: Diferencia de costo
- **WHEN** el producto solicitado tiene un costo distinto al del producto actual
- **THEN** el administrador ve la diferencia de costo antes de resolver la modificación

### Requirement: Situación física por cantidades
Cuando el detalle tenga cantidad despachada mayor a cero, el sistema SHALL exigir al solicitar la modificación que el almacenero declare cuántas unidades están preparadas en almacén y cuántas fueron entregadas físicamente al solicitante, y SHALL exigir que ambas sumen exactamente la cantidad despachada del detalle. Se admiten situaciones mixtas.

#### Scenario: Situación mixta
- **WHEN** un detalle tiene 2 unidades despachadas y el almacenero declara 1 preparada y 1 entregada físicamente
- **THEN** la modificación registra ambas cantidades y requiere la devolución de la unidad entregada

#### Scenario: Cantidades que no suman lo despachado
- **WHEN** el almacenero declara cantidades preparadas y entregadas cuya suma difiere de la cantidad despachada
- **THEN** el sistema rechaza la solicitud indicando la inconsistencia

#### Scenario: Detalle sin despacho previo
- **WHEN** el almacenero solicita una modificación sobre un detalle con cantidad despachada 0
- **THEN** el sistema no solicita situación física y registra cantidad despachada previa 0

### Requirement: Una sola modificación abierta por detalle
El sistema SHALL impedir registrar una nueva modificación para un detalle mientras exista otra abierta (`pendiente` o `aprobada` a la espera de devolución) para ese mismo detalle, incluso ante solicitudes simultáneas.

#### Scenario: Segunda solicitud sobre el mismo detalle
- **WHEN** existe una modificación abierta para un detalle y el almacenero intenta registrar otra
- **THEN** el sistema rechaza la operación indicando que ya existe una modificación abierta

#### Scenario: Detalles distintos de la misma solicitud
- **WHEN** existe una modificación abierta para un detalle y se solicita otra para un detalle diferente
- **THEN** el sistema acepta la nueva solicitud

#### Scenario: Solicitudes simultáneas
- **WHEN** dos usuarios registran al mismo tiempo una modificación para el mismo detalle
- **THEN** solo una se crea y la otra recibe el mensaje de modificación abierta

### Requirement: Resolución exclusiva del administrador
El sistema SHALL permitir únicamente a usuarios con permiso de aprobación aprobar o rechazar una modificación `pendiente`, y SHALL impedir que el almacenero cambie directamente el producto de una solicitud aprobada. Una modificación SHALL poder resolverse una sola vez, aun cuando dos administradores actúen simultáneamente.

#### Scenario: Almacenero intenta resolver
- **WHEN** un usuario sin permiso de aprobación intenta aprobar o rechazar una modificación
- **THEN** el sistema responde con acceso denegado y la modificación permanece `pendiente`

#### Scenario: Administrador rechaza
- **WHEN** el administrador rechaza una modificación indicando una nota de resolución
- **THEN** la modificación pasa a `rechazada`
- **AND** el detalle conserva el producto original y no se habilita sustitución

#### Scenario: Doble resolución simultánea
- **WHEN** dos administradores intentan resolver a la vez la misma modificación
- **THEN** solo la primera resolución se aplica y la segunda es rechazada porque la modificación ya no está pendiente

### Requirement: Historial inmutable
Una modificación en estado `aplicada`, `rechazada` o `cancelada` SHALL ser inmutable: no puede volver a otro estado ni editarse ni eliminarse. Los registros de devolución SHALL ser inmutables una vez creados.

#### Scenario: Intento de reabrir una modificación aplicada
- **WHEN** cualquier operación intenta cambiar el estado o los datos de una modificación `aplicada`, `rechazada` o `cancelada`
- **THEN** el sistema rechaza la operación y el registro permanece sin cambios

### Requirement: Resolución de compras asociadas por línea y producto
Para aprobar una modificación, el administrador SHALL resolver cada línea de compra abierta (orden `pendiente` o `aprobada`, línea no cancelada) generada por faltante para ese mismo detalle **y** para el producto reemplazado, eligiendo cancelar la línea o mantenerla con una nota obligatoria. La cancelación SHALL aplicarse a nivel de línea; la orden completa SHALL pasar a `cancelada` solo cuando todas sus líneas estén canceladas. El sistema SHALL NOT cancelar compras sin decisión explícita, SHALL NOT permitir aprobar con líneas abiertas sin resolver, y SHALL NOT reasignar al nuevo producto las líneas mantenidas. Las líneas de órdenes `completada` conservan el ingreso.

#### Scenario: Cancelar una línea de una orden con varios productos
- **WHEN** una orden `pendiente` contiene mantequilla con sal, aceite y azúcar y el administrador cancela la línea de mantequilla con sal
- **THEN** solo esa línea queda cancelada y la orden permanece con las demás líneas activas

#### Scenario: Cancelar la única línea de una orden
- **WHEN** el administrador cancela la única línea activa de una orden
- **THEN** la orden pasa a `cancelada`

#### Scenario: Línea mantenida
- **WHEN** el administrador mantiene una línea del producto original con una nota
- **THEN** la línea permanece vinculada al producto original y la decisión y la nota quedan registradas en la modificación

#### Scenario: Aprobación sin resolver compras
- **WHEN** el administrador intenta aprobar sin decidir sobre una línea abierta asociada
- **THEN** el sistema rechaza la aprobación indicando las líneas pendientes de resolver

#### Scenario: Líneas de otros productos o detalles
- **WHEN** el mismo detalle tiene líneas abiertas de otros productos o existen líneas del producto original ligadas a otros detalles
- **THEN** esas líneas no se incluyen en la resolución de la modificación

### Requirement: Aplicación según la situación física
Al aprobarse una modificación, el sistema SHALL registrar la cantidad despachada previa. Si no hay unidades entregadas físicamente, SHALL cancelar la preparación (cantidad despachada 0), actualizar el detalle al producto y la cantidad solicitados y marcar la modificación `aplicada` de inmediato. Si hay unidades entregadas físicamente, SHALL dejar la modificación `aprobada` esperando devolución, mantener el producto original y bloquear el detalle hasta completar la devolución. El sistema SHALL NOT registrar movimientos de Kardex en este flujo.

#### Scenario: Solo unidades preparadas en almacén
- **WHEN** el administrador aprueba una modificación con todas las unidades preparadas en almacén
- **THEN** el detalle queda con el producto y cantidad solicitados y cantidad despachada 0
- **AND** la modificación queda `aplicada`
- **AND** no se registra ningún movimiento de Kardex

#### Scenario: Unidades entregadas físicamente
- **WHEN** el administrador aprueba una modificación con unidades entregadas físicamente
- **THEN** la modificación queda `aprobada` a la espera de devolución
- **AND** el detalle conserva el producto original y queda bloqueado

#### Scenario: Otros productos no se afectan
- **WHEN** se aprueba una modificación sobre un detalle de una solicitud con varios productos
- **THEN** los demás detalles conservan producto, cantidades y estado de despacho

### Requirement: Devolución física parcial y completa
El sistema SHALL permitir al almacenero registrar devoluciones sucesivas sobre una modificación `aprobada`, cada una con cantidad, fecha, responsable y nota, sin que el total devuelto supere las unidades entregadas físicamente. El sistema SHALL aplicar la modificación (cantidad despachada 0, producto y cantidad solicitados) únicamente cuando el total devuelto iguale las unidades entregadas físicamente. Una devolución parcial SHALL NOT reiniciar el despacho ni habilitar el producto sustituto, y una nota SHALL NOT sustituir una devolución física.

#### Scenario: Devolución completa
- **WHEN** se entregaron 2 unidades y el almacenero registra una devolución de 2
- **THEN** la modificación se aplica, el detalle queda con el producto solicitado y cantidad despachada 0 y el bloqueo se levanta

#### Scenario: Devolución parcial
- **WHEN** se entregaron 2 unidades y el almacenero registra una devolución de 1
- **THEN** la devolución queda registrada
- **AND** el detalle permanece bloqueado con el producto original y la modificación sigue `aprobada`

#### Scenario: Devoluciones sucesivas
- **WHEN** tras una devolución de 1 el almacenero registra otra devolución de 1
- **THEN** el total devuelto iguala lo entregado y la modificación se aplica

#### Scenario: Exceso de devolución
- **WHEN** el almacenero intenta registrar una devolución que haría superar el total devuelto a lo entregado
- **THEN** el sistema rechaza la devolución

#### Scenario: Despacho antes de completar la devolución
- **WHEN** el almacenero intenta despachar el detalle sin que la devolución esté completa
- **THEN** el sistema rechaza el despacho de ese detalle

### Requirement: Cancelación de una modificación con devolución incompleta
El sistema SHALL permitir al administrador cancelar una modificación `aprobada` cuya devolución no se complete, con nota obligatoria. Al cancelar, el detalle SHALL recuperar el estado normal con su producto original y su cantidad despachada SHALL reducirse en las unidades efectivamente devueltas.

#### Scenario: Cancelación tras devolución parcial
- **WHEN** se entregaron 2 unidades, se devolvió 1 y el administrador cancela la modificación con una nota
- **THEN** la modificación pasa a `cancelada`
- **AND** el detalle conserva el producto original con cantidad despachada 1 y sin bloqueo

### Requirement: Bloqueos mientras existan modificaciones abiertas
Mientras exista una modificación abierta sobre un detalle, el sistema SHALL impedir despachar ese detalle; mientras exista alguna en la solicitud, SHALL impedir recepcionarla y cancelarla.

#### Scenario: Recepción con modificación abierta
- **WHEN** el solicitante intenta confirmar la recepción de una solicitud con una modificación abierta
- **THEN** el sistema rechaza la recepción indicando la modificación abierta

#### Scenario: Cancelación con modificación abierta
- **WHEN** se intenta cancelar una solicitud con una modificación abierta
- **THEN** el sistema rechaza la cancelación

### Requirement: Aprobación independiente de las existencias
El sistema SHALL aprobar la modificación sin considerar el stock del producto solicitado y SHALL NOT permitir entregar el producto original como reemplazo.

#### Scenario: Producto correcto sin existencias
- **WHEN** el administrador aprueba el cambio a un producto con stock 0 y el almacenero despacha la solicitud
- **THEN** el detalle queda en `pending_stock` con cantidad despachada 0
- **AND** el sistema genera la compra por el faltante del producto correcto, enlazada a la solicitud y al detalle
- **AND** el producto original no puede entregarse en ese detalle

### Requirement: Sin compras duplicadas por el mismo faltante
El sistema SHALL generar compras automáticas solo por el faltante no cubierto por líneas abiertas enlazadas al mismo detalle **y** al mismo producto.

#### Scenario: Segundo despacho con el faltante ya cubierto
- **WHEN** existe una línea abierta del mismo producto y detalle que cubre el faltante y se ejecuta otro despacho que lo mantiene
- **THEN** el sistema no crea una nueva compra para ese detalle

#### Scenario: Línea mantenida del producto original
- **WHEN** se mantuvo una línea abierta del producto original y el detalle ya cambió al nuevo producto sin existencias
- **THEN** el sistema genera la compra del nuevo producto porque la línea del producto original no cubre esa necesidad

### Requirement: Bandeja y notificaciones tras confirmar la transacción
El sistema SHALL ofrecer al administrador una bandeja de modificaciones pendientes con la solicitud original y sus demás productos, y SHALL notificar en tiempo real la creación, la resolución, las devoluciones y la cancelación solo después de que la transacción correspondiente se confirme.

#### Scenario: Notificación al administrador
- **WHEN** el almacenero registra una modificación
- **THEN** el administrador recibe una notificación en tiempo real y la modificación aparece en su bandeja

#### Scenario: Aprobación revertida
- **WHEN** la transacción de aprobación falla y se revierte
- **THEN** no se emite ninguna notificación de aprobación

#### Scenario: Notificación al almacenero
- **WHEN** el administrador aprueba, rechaza o cancela la modificación
- **THEN** el almacenero que la solicitó recibe una notificación con el resultado

### Requirement: Integridad histórica de detalles y compras
El sistema SHALL impedir la eliminación física de detalles de solicitud que tengan líneas de compra o modificaciones asociadas, y SHALL conservar el vínculo histórico entre compras, solicitudes y detalles.

#### Scenario: Eliminar un detalle con compra asociada
- **WHEN** se intenta eliminar físicamente un detalle con una línea de compra enlazada
- **THEN** el sistema rechaza la eliminación y conserva el vínculo
