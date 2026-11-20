# Spec Delta: orders-registration-history-module

## Purpose

Permitir registrar, almacenar, consultar e imprimir reservas y pedidos personalizados de clientes en un historial centralizado, incluyendo datos del cliente, entrega, múltiples productos con características flexibles, fotografías de referencia comprimidas y convertidas a `.webp`, complementos (`Cliente` / `Negocio`), pagos parciales y cálculo automático de saldo pendiente.

## ADDED Requirements

### Requirement: Registro de Clientes y Vinculación de Múltiples Pedidos
El sistema SHALL almacenar la información de los clientes (`full_name`, `phone`) en la entidad `Customer` y permitir vincular múltiples pedidos (`Order`) a un mismo cliente, reutilizando el registro existente cuando coincida el número de celular o creando uno nuevo automáticamente.

#### Scenario: Autocompletar o vincular cliente existente por número de celular
- **WHEN** el usuario ingresa el celular o nombre de un cliente previamente registrado al crear un pedido
- **THEN** el sistema sugiere o vincula el registro del cliente existente y permite consultar sus pedidos anteriores

### Requirement: Registro Completo de Reserva con Productos, Especificaciones Flexibles y Complementos
El sistema SHALL permitir registrar un pedido (`Order`) con correlativo automático (`#00001`), fecha de registro, fecha y hora programada de entrega, modalidad de entrega (`Recoger en tienda` o `Entrega a domicilio` con dirección), uno o varios productos (`OrderItem`) con cantidad, porciones/tamaño, decoración/descripción personalizada, observaciones sobre referencias (`TAL CUAL IMAGEN`), múltiples especificaciones personalizadas clave-valor (`OrderItemDetail`) y complementos (`OrderComplement`) indicando el responsable (`Cliente` o `Negocio`).

#### Scenario: Registrar pedido de torta personalizada con características y complementos
- **WHEN** el usuario registra un pedido para un cliente indicando modalidad de entrega, un producto con especificaciones (`Sabor de masas`, `Rellenos`, `Alto`), decoración detallada y complementos asignados a `Cliente` o `Negocio`
- **THEN** el sistema almacena de forma transaccional el pedido junto con sus productos, especificaciones y complementos asignándole un número correlativo de reserva (`#00001`)

### Requirement: Compresión y Conversión Obligatoria de Fotografías de Referencia a Formato WebP
El sistema SHALL permitir adjuntar múltiples fotografías de referencia (`OrderAttachment`) por cada producto solicitado, comprimiendo primero cada imagen y convirtiéndola obligatoriamente al formato `.webp` tanto en el cliente (`useImageCompression`) como en el servidor (`OrderService`) antes de almacenarla en disco.

#### Scenario: Subida de fotografías de referencia por producto
- **WHEN** el usuario adjunta una o varias fotografías de diseño/referencia a un producto del pedido
- **THEN** el frontend comprime la imagen (máx. `0.8 MB`, `1280px`) y la convierte a `.webp` antes del envío, y el backend valida y almacena el archivo final con extensión `.webp` vinculado al producto

### Requirement: Control Económico de Total, Descuento, Pagos Parciales y Saldo Pendiente Automático
El sistema SHALL registrar el total del pedido, descuento opcional, observaciones sobre el pago y múltiples pagos/abonos (`OrderPayment`) con su método de pago (`QR`, `Efectivo`, `Transferencia`, `Tarjeta`), calculando automáticamente el `saldo pendiente` (`balance_due = max(0, (subtotal - discount) - paid_amount)`).

#### Scenario: Cálculo automático de saldo pendiente con pago inicial y abonos posteriores
- **WHEN** se registra un pedido con Total `Bs 215` y un anticipo por `Pago QR` de `Bs 50`
- **THEN** el sistema calcula y persiste automáticamente un `Saldo pendiente` de `Bs 165`, y permite registrar abonos adicionales posteriores desde el historial actualizando el saldo pendiente

### Requirement: Historial Centralizado de Pedidos, Búsqueda, Filtros y Comprobante PDF
El sistema SHALL proveer una vista de historial (`/admin/orders`) que permita buscar por nombre o celular del cliente y por número de reserva, filtrar por rango de fecha de registro, rango de fecha de entrega y por cliente específico, visualizar el detalle completo de cada reserva, consultar pagos y saldos pendientes, y generar/imprimir el comprobante PDF de la reserva.

#### Scenario: Consulta en el historial e impresión de comprobante de reserva
- **WHEN** el usuario busca un pedido por celular, nombre o número de reserva (`#00001`) en `/admin/orders` y abre su detalle o solicita imprimir el comprobante
- **THEN** el sistema muestra toda la ficha de la reserva (cliente, entrega, productos, especificaciones, galería de fotos `.webp`, complementos y desglose económico) y genera el comprobante PDF listo para imprimir o descargar

