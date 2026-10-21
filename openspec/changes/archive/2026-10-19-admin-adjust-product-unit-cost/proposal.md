# Proposal

## Why

Durante la carga o importación inicial del catálogo de productos, varios productos fueron registrados con un costo unitario erróneo. Actualmente el sistema no cuenta con una interfaz web administrativa para corregir el costo unitario de un producto sin alterar sus cantidades físicas de stock, lo que impide subsanar errores de digitación y provoca que la valorización del inventario y el Kardex no reflejen el valor financiero real. Se requiere permitir que exclusivamente el rol Administrador (`admin` o `Super Admin`) pueda ajustar el costo unitario de un producto desde la interfaz web, recalculando la valorización monetaria en cadena sin alterar el stock físico ni ningún otro parámetro.

## What Changes

- **Interfaz de edición de costo en Productos:** En la vista de administración de productos (`resources/js/Pages/Admin/Product/Index.vue`), se añade una acción accesible exclusivamente para usuarios con rol o permiso de administrador (ej. botón o modal dedicado "Ajustar Costo").
- **Modal y Formulario de Ajuste de Costo:** Modal intuitivo y protegido que muestra el producto, stock actual (en solo lectura), costo unitario actual y un campo numérico para ingresar el nuevo costo unitario con justificación/motivo.
- **Endpoint y Form Request de Ajuste de Costo:** Endpoint protegido (`POST /admin/products/{product}/adjust-cost`) validado con `AdjustProductCostRequest` que comprueba autorización de administrador y valor numérico mayor o igual a 0.
- **Servicio de Recálculo en Cascada (`ProductCostAdjustmentService`):**
  - Mantiene intacta la cantidad física (`quantity`) en `stocks` y en el histórico.
  - Actualiza el `average_cost` e `inventory_value` (`quantity * new_cost`) en la tabla `stocks`.
  - Registra o sincroniza el Kardex contable asegurando que el saldo valorizado (`balance_total_cost`, `value_after`, `avg_cost`) cuadre de forma matemática exacta con el nuevo costo.
  - Si el producto proviene de la compra/importación inicial sin movimientos intermedios, actualiza también el detalle de compra origen (`purchase_details`) y el total de la compra para coherencia documental total.
- **Control de Acceso Estricto:** Restringido a usuarios con rol `admin` o `Super Admin` mediante middleware/policy/gate.

## Capabilities

### New Capabilities
- `admin-product-unit-cost-adjustment`: Permite a los administradores modificar el costo unitario de un producto desde la interfaz web, recalculando la valorización monetaria en almacén y Kardex sin alterar el stock ni otros parámetros.

### Modified Capabilities
<!-- Ninguna capability existente cambia sus requerimientos de negocio previos -->

## Impact

- **Backend:** Nuevo servicio `ProductCostAdjustmentService`, nuevo Form Request `AdjustProductCostRequest`, nuevo endpoint en `routes/admin.php` o controlador `ProductController`.
- **Frontend:** Modificación en `Product/Index.vue` para incluir la opción visual y modal responsivo bajo verificación de rol admin.
- **Base de Datos:** No requiere migraciones estructurales; utiliza los campos existentes en `stocks`, `kardexes`, y `purchase_details`.
