# Proposal: Módulo de Registro e Historial de Pedidos (`orders-registration-history-module`)

## Why

El negocio necesita digitalizar el registro manual de reservas y solicitudes personalizadas de clientes (por ejemplo, tortas personalizadas, pastelería y pedidos especiales), conservando en un historial centralizado toda la información acordada: datos del cliente, fecha/hora y modalidad de entrega, múltiples productos con características flexibles (sabor de masas, rellenos, altura, decoración), fotografías de referencia optimizadas a `.webp`, complementos con su responsable (`Cliente` o `Negocio`), e información económica (total, descuento, abonos por método de pago y saldo pendiente automático), sin requerir estados complejos de producción o seguimiento.

## What Changes

- **Esquema de Base de Datos y Modelos Eloquent (`diagramas-entidad-relacion`, `mejores-practicas-fullstack`)**:
  - Creación de las 7 entidades con PK `UUID` e índices optimizados en PostgreSQL:
    1. `Customer` (`customers`): Nombre completo, celular (indexado), dirección y notas; un cliente puede tener múltiples pedidos.
    2. `Order` (`orders`): Número correlativo de reserva (`#00001`), cliente, fechas de registro y entrega, hora de entrega, modalidad (`pickup` = Recoger en tienda, `delivery` = Entrega a domicilio), dirección de entrega, total, descuento opcional, monto pagado, saldo pendiente calculado automáticamente y observaciones de pago.
    3. `OrderItem` (`order_items`): Productos incluidos en el pedido (cantidad, porciones/tamaño, decoración/descripción personalizada, observaciones sobre referencias como `TAL CUAL IMAGEN` y observaciones adicionales).
    4. `OrderItemDetail` (`order_item_details`): Especificaciones personalizadas clave-valor flexibles por producto (ej. `Sabor de masas`, `Rellenos`, `Alto`).
    5. `OrderAttachment` (`order_attachments`): Fotografías de referencia e imágenes de diseño por producto/pedido, comprimidas primero y convertidas a `.webp` (`optimizacion-imagenes-webp`).
    6. `OrderComplement` (`order_complements`): Complementos y accesorios adicionales con su responsable (`client` = Cliente, `business` = Negocio).
    7. `OrderPayment` (`order_payments`): Registro de anticipos y pagos parciales con método de pago (`QR`, `Efectivo`, `Transferencia`, `Tarjeta`) y fecha.
- **Capa de Dominio, Servicios, Enums, FormRequests y Resources (`solid-laravel`, `inertia-optimizacion-payload`)**:
  - Enums tipados en PHP 8.2+ (`OrderDeliveryModeEnum`, `OrderComplementProviderEnum`).
  - `OrderService` y `OrderFacade` para el registro transaccional del pedido, vinculación/autocompletado de cliente, procesamiento de imágenes a `.webp`, cálculo automático de saldos y registro de pagos/abonos adicionales.
  - `StoreOrderRequest` y `StoreOrderPaymentRequest` para validación estricta.
  - `OrderResource` y `CustomerResource` para enviar payloads limpios y sin overfetching a Inertia/Vue.
- **Vistas Frontend Vue 3 + Comprobante PDF (`experto-ux-ui`, `diseno-responsivo-tailwind`, `modo-oscuro-fullstack`)**:
  - **Formulario de Registro de Pedido (`/admin/orders/create`)**: Autocompletado de cliente por celular/nombre, datos de entrega, constructor dinámico de múltiples productos con especificaciones flexibles, carga de múltiples fotos por producto con compresión y conversión previa a `.webp` en navegador (`useImageCompression`), complementos (`Cliente` / `Negocio`) y resumen económico con cálculo en vivo del saldo pendiente.
  - **Historial y Consulta de Pedidos (`/admin/orders`)**: Búsqueda por `# Reserva`, nombre o celular del cliente, filtros por fecha de registro, fecha de entrega, modalidad y cliente específico (para ver pedidos anteriores de un mismo cliente), paginador interactivo completo, modal de detalle completo de la reserva (estilo ficha `Reserva #00001`), modal para registrar nuevos abonos sobre saldos pendientes, y generación/impresión del comprobante PDF de la reserva.
  - **Integración en Menú Lateral (`SidebarService`)**: Nuevo grupo `Pedidos` con accesos a `Registrar Pedido` e `Historial de Pedidos`.

## Capabilities

### New Capabilities
- `orders-registration-history-module`: Registro, almacenamiento, procesamiento de imágenes `.webp`, control de pagos/saldos, generación de comprobante PDF y consulta histórica de reservas y pedidos de clientes.

### Modified Capabilities
<!-- Ninguna capacidad existente modifica sus requisitos -->

## Impact

- **Migraciones**: Creación de tablas `customers`, `orders`, `order_items`, `order_item_details`, `order_attachments`, `order_complements`, `order_payments` y permiso `manage-orders`.
- **Modelos y Enums**: `app/Models/Customer.php`, `app/Models/Order.php`, `app/Models/OrderItem.php`, `app/Models/OrderItemDetail.php`, `app/Models/OrderAttachment.php`, `app/Models/OrderComplement.php`, `app/Models/OrderPayment.php`, `app/Enums/OrderDeliveryModeEnum.php`, `app/Enums/OrderComplementProviderEnum.php`.
- **Servicios, Requests y Resources**: `app/Services/OrderService.php`, `app/Facades/OrderFacade.php`, `app/Http/Requests/Admin/Order/StoreOrderRequest.php`, `app/Http/Requests/Admin/Order/StoreOrderPaymentRequest.php`, `app/Http/Resources/OrderResource.php`, `app/Http/Controllers/Admin/OrderController.php`.
- **Frontend y PDF**: `resources/js/Pages/Admin/Orders/Index.vue`, `resources/js/Pages/Admin/Orders/Create.vue`, `resources/js/Pages/Admin/Orders/Partials/OrderDetailModal.vue`, `resources/js/Pages/Admin/Orders/Partials/OrderPaymentModal.vue`, `resources/views/exports/orders/order-receipt-pdf.blade.php`.

