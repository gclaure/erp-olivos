# Tasks

## 1. Backend: Form Request, Servicio y Rutas

- [x] 1.1 Crear `app/Http/Requests/Admin/AdjustProductCostRequest.php` con autorización estricta para rol admin / super_admin y validación de costo unitario numérico mayor o igual a cero.
- [x] 1.2 Implementar `app/Services/ProductCostAdjustmentService.php` con transacción atómica en base de datos para preservar el stock físico (`quantity`), actualizar `average_cost` e `inventory_value` en `stocks`, sincronizar los saldos contables en `kardexes` y actualizar los registros origen en `purchase_details` y `purchases`.
- [x] 1.3 Agregar método `adjustCost` en `app/Http/Controllers/Admin/ProductController.php` y registrar la ruta protegida `POST /admin/products/{product}/adjust-cost` en `routes/admin.php`.

## 2. Frontend: Integración en Vista de Productos

- [x] 2.1 Asegurar que `ProductController` o `ProductResource` envíen el costo promedio actual (`average_cost`) y el stock actual para su visualización en la tabla de productos.
- [x] 2.2 Implementar en `resources/js/Pages/Admin/Product/Index.vue` el botón de acción "Ajustar Costo" visible únicamente para administradores y el modal responsivo con Tailwind CSS que muestre stock actual (solo lectura), costo anterior y nuevo costo a ingresar.
- [x] 2.3 Conectar el formulario de ajuste con Inertia, mostrando feedback de éxito/error mediante SweetAlert2 y actualizando la tabla sin recargar la página completa.

## 3. Validación y Verificación

- [x] 3.1 Validar mediante prueba de ajuste que el stock físico no cambia, el costo promedio se actualiza y la valorización en Kardex y Almacén cuadra matemáticamente.
- [x] 3.2 Verificar que una solicitud con rol no administrador o usuario no autenticado sea rechazada con código HTTP 403 Forbidden.
