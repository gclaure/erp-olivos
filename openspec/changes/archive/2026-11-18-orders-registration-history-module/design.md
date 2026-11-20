# Design: Módulo de Registro e Historial de Pedidos (`orders-registration-history-module`)

## Context

Se requiere un módulo para digitalizar las reservas y pedidos personalizados de clientes sin estados de producción ni flujo de planta, enfocado 100% en la fidelidad del registro (productos con especificaciones flexibles, decoración, fotografías de referencia en `.webp`, complementos de cliente/negocio, anticipos/saldos) y en la facilidad de consulta histórica y reimpresión del comprobante.

## Goals / Non-Goals

**Goals:**
- Modelar las 7 entidades (`Customer`, `Order`, `OrderItem`, `OrderItemDetail`, `OrderAttachment`, `OrderComplement`, `OrderPayment`) siguiendo las reglas de `diagramas-entidad-relacion` (UUIDs, claves foráneas e índices PostgreSQL en `phone`, `full_name`, `number`, `registered_at`, `delivery_date`).
- Aplicar arquitectura limpia (`solid-laravel`, `mejores-practicas-fullstack`) con Enums de PHP 8.2+, `OrderService`, `OrderFacade`, `FormRequest` y `JsonResource` (`inertia-optimizacion-payload`).
- Procesar todas las imágenes de referencia comprimiéndolas primero y convirtiéndolas a `.webp` (`optimizacion-imagenes-webp`).
- Proveer interfaces responsivas y con soporte modo oscuro (`diseno-responsivo-tailwind`, `modo-oscuro-fullstack`, `experto-ux-ui`) para el registro (`/admin/orders/create`) y el historial (`/admin/orders`), además del comprobante PDF de la reserva.

**Non-Goals:**
- No gestionar estados de producción, horneado, preparación ni despacho de almacén.
- No descontar stock de inventario de insumos al registrar una reserva de cliente.

## Decisions

1. **Estructura Relacional de 7 Tablas con UUIDs e Índices PostgreSQL (`diagramas-entidad-relacion`)**:
   - *Decisión*: Crear una migración estructurada con las 7 tablas (`customers`, `orders`, `order_items`, `order_item_details`, `order_attachments`, `order_complements`, `order_payments`). Indexar `customers.phone`, `customers.full_name`, `orders.number`, `orders.registered_at`, `orders.delivery_date`, `orders.customer_id` y todas las claves foráneas.
   - *Racional*: Garantiza búsquedas instantáneas por celular, nombre, número de reserva y rangos de fechas en PostgreSQL.

2. **Especificaciones Flexibles Clave-Valor por Producto (`OrderItemDetail`)**:
   - *Decisión*: Cada `OrderItem` tiene una relación `hasMany(OrderItemDetail::class)` con pares `label` / `value` (ej. `Sabor de masas: chocolate-vainilla-chocolate`, `Rellenos: Los respectivos`, `Alto: 8 cm`), además de los campos dedicados `custom_description` (decoración), `reference_notes` (`TAL CUAL IMAGEN`) y `observations`. En el frontend (`Create.vue`), ofrecer botones de inserción rápida para los atributos más comunes de pastelería (`Sabor de masas`, `Rellenos`, `Alto`, `Forma`, `Dedicatoria`) y permitir añadir cualquier atributo personalizado.
   - *Racional*: Cumple la consideración de que no todos los productos tienen las mismas características sin perder rapidez de captura para tortas y postres.

3. **Pipeline de Compresión + Conversión a `.webp` en Doble Capa (`optimizacion-imagenes-webp`)**:
   - *Decisión*:
     1. En Vue 3 (`Create.vue`), al seleccionar imágenes en cada producto, ejecutar `useImageCompression().compressAndConvertToWebP(file, { maxSizeMB: 0.8, maxWidthOrHeight: 1280, fileType: 'image/webp' })` antes de adjuntarlas al `useForm` de Inertia.
     2. En Laravel (`OrderService::storeAttachmentImage()`), procesar el archivo con GD (`imagecreatefromstring` + `imagepalettetotruecolor` + `imagewebp($img, null, 80)`) guardándolo como `orders/attachments/ord_<uuid>.webp` en el disco `public`.
   - *Racional*: Garantiza que primero se comprima la imagen y luego se almacene siempre en formato `.webp` con mínimo peso y alta nitidez visual.

4. **Cálculo Automático de Saldo Pendiente y Registro de Abonos (`OrderPayment`)**:
   - *Decisión*: En `OrderService`, calcular `total = max(0, subtotal - discount)`, `paid_amount = sum(payments.amount)` y `balance_due = max(0, total - paid_amount)`. Permitir registrar un pago inicial al crear el pedido y pagos adicionales desde el historial (`POST /admin/orders/{order}/payments`) recalculando `paid_amount` y `balance_due` dentro de una transacción DB.
   - *Racional*: Evita inconsistencias aritméticas y permite saldar el pedido cuando el cliente recoge su reserva.

5. **Comprobante PDF de Reserva (`order-receipt-pdf.blade.php`)**:
   - *Decisión*: Implementar `GET /admin/orders/{order}/receipt-pdf` con DomPDF generando el comprobante con el formato exacto del ejemplo (`Reserva #00001`, Cliente, Fecha y Modalidad de Entrega, Productos solicitados con sus características, Decoración, Referencia e imágenes `.webp` embebidas en base64, Complementos con indicador `Cliente`/`Negocio`, e Información económica con `Total`, `Pagos realizados` y `Saldo pendiente`).
   - *Racional*: Permite imprimir o compartir el comprobante oficial de la reserva en cualquier momento desde el historial.

## Risks / Trade-offs

- **[Risk] Subida de múltiples fotos de alta resolución desde cámaras móviles** → **Mitigación**: Compresión previa en cliente con `browser-image-compression` (`useImageCompression`) a `.webp` (`<= 0.8 MB`) antes de enviar el request multipart a Laravel.

