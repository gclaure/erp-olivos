# Tasks: Módulo de Registro e Historial de Pedidos (`orders-registration-history-module`)

## 1. Base de Datos y Migraciones (Skill: `diagramas-entidad-relacion`)

- [x] 1.1 Crear la migración `create_orders_module_tables.php` con las 7 tablas (`customers`, `orders`, `order_items`, `order_item_details`, `order_attachments`, `order_complements`, `order_payments`), claves primarias UUID, claves foráneas con cascada e índices PostgreSQL en `phone`, `full_name`, `number`, `registered_at` y `delivery_date`.
- [x] 1.2 Agregar el permiso `manage-orders` y asignarlo a los roles `Admin` / `Super Admin` en la migración, ejecutando la migración para verificar el esquema.

## 2. Enums y Modelos Eloquent (Skills: `solid-laravel`, `mejores-practicas-fullstack`)

- [x] 2.1 Crear los Enums tipados `app/Enums/OrderDeliveryModeEnum.php` (`pickup` = Recoger en tienda, `delivery` = Entrega a domicilio) y `app/Enums/OrderComplementProviderEnum.php` (`client` = Cliente, `business` = Negocio) con `declare(strict_types=1);` y etiquetas legibles.
- [x] 2.2 Crear los modelos `Customer.php` y `Order.php` con `$fillable`, `$casts`, relaciones tipadas (`HasMany`, `BelongsTo`), accessor `formatted_number` (`#00001`) y scopes de búsqueda/filtrado.
- [x] 2.3 Crear los modelos `OrderItem.php`, `OrderItemDetail.php`, `OrderAttachment.php`, `OrderComplement.php` y `OrderPayment.php` con `declare(strict_types=1);`, `$fillable`, `$casts` y relaciones explícitas.

## 3. Servicio de Dominio, Procesamiento WebP y Facade (Skills: `solid-laravel`, `optimizacion-imagenes-webp`)

- [x] 3.1 Crear `app/Services/OrderService.php` y `app/Facades/OrderFacade.php` (registrado en `AppServiceProvider`) con la lógica para buscar/crear `Customer` por celular, generar el correlativo `number` (`#00001`), calcular `total`, `paid_amount` y `balance_due`, y persistir el pedido completo en transacción DB.
- [x] 3.2 Implementar en `OrderService` el método `processAndStoreAttachmentImage(UploadedFile $file)` que comprima y convierta cualquier imagen subida al formato `.webp` (calidad 80) en `storage/app/public/orders/attachments/`, además del método `addPayment(Order $order, array $data)` para registrar abonos posteriores y recalcular el saldo pendiente.

## 4. Validación (`FormRequests`), Transformación (`Resources`) y Controlador (Skills: `mejores-practicas-fullstack`, `inertia-optimizacion-payload`)

- [x] 4.1 Crear `app/Http/Requests/Admin/Order/StoreOrderRequest.php` y `app/Http/Requests/Admin/Order/StoreOrderPaymentRequest.php` con reglas de validación completas para cliente, entrega, productos, especificaciones, imágenes, complementos y pagos.
- [x] 4.2 Crear `app/Http/Resources/OrderResource.php` y `app/Http/Resources/CustomerResource.php` para transformar los datos sin N+1 ni overfetching hacia Inertia/Vue.
- [x] 4.3 Crear `app/Http/Controllers/Admin/OrderController.php` (`index`, `create`, `store`, `show`, `addPayment`, `searchCustomers`, `receiptPdf`), registrar las rutas en `routes/admin.php` y añadir el menú **Pedidos** (`Registrar Pedido` e `Historial de Pedidos`) en `app/Services/SidebarService.php`.

## 5. Comprobante PDF de Reserva (Skills: `experto-ux-ui`, `mejores-practicas-fullstack`)

- [x] 5.1 Crear la vista `resources/views/exports/orders/order-receipt-pdf.blade.php` con la identidad visual de Los Olivos y la estructura exacta del ejemplo (`Reserva #00001`, Cliente, Fecha y Modalidad de Entrega, Productos con características, Decoración, Referencia y miniaturas `.webp`, Complementos `Cliente`/`Negocio`, y Resumen Económico con `Total`, `Pagos realizados` y `Saldo pendiente`).

## 6. Frontend Vue 3: Registro de Pedido con Compresión WebP (Skills: `optimizacion-imagenes-webp`, `diseno-responsivo-tailwind`, `modo-oscuro-fullstack`, `experto-ux-ui`)

- [x] 6.1 Crear `resources/js/Pages/Admin/Orders/Create.vue` con autocompletado de cliente (por celular/nombre), datos de reserva y entrega (`Recoger en tienda` / `Entrega a domicilio`), constructor de múltiples productos con chips rápidos de especificaciones (`Sabor de masas`, `Rellenos`, `Alto`) + especificaciones libres, decoración, complementos (`Cliente` / `Negocio`) y resumen económico reactivo (`Total`, `Descuento`, `Pagos` y `Saldo pendiente`).
- [x] 6.2 Integrar en `Create.vue` la carga de múltiples fotografías de referencia por producto utilizando `useImageCompression` (`compressAndConvertToWebP`) para comprimir primero cada imagen y convertirla a `.webp` en el navegador con previsualización instantánea antes de guardar.

## 7. Frontend Vue 3: Historial, Consulta, Abonos y Paginador (Skills: `diseno-responsivo-tailwind`, `modo-oscuro-fullstack`, `inertia-optimizacion-payload`)

- [x] 7.1 Crear `resources/js/Pages/Admin/Orders/Index.vue` con buscador por `# Reserva`, nombre o celular del cliente, filtros por fecha de registro, fecha de entrega y cliente específico ("Ver pedidos de este cliente"), tabla responsiva + vista móvil en tarjetas, y el paginador interactivo completo (`Mostrando del X al Y total de Z`, `«`, `‹`, páginas, `›`, `»` y selector `10/25/50/100`).
- [x] 7.2 Crear los modales `resources/js/Pages/Admin/Orders/Partials/OrderDetailModal.vue` (ficha completa estilo `Reserva #00001` con visor de fotos `.webp` y botón de comprobante PDF) y `resources/js/Pages/Admin/Orders/Partials/OrderPaymentModal.vue` (para registrar abonos adicionales y actualizar el saldo), compilando con `npm run build`.

