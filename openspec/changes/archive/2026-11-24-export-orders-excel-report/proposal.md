# Proposal

## Why

Actualmente, el módulo de Pedidos (`/admin/orders`) permite consultar, buscar y filtrar el historial de reservas de clientes de forma interactiva en la interfaz web, pero carece de un mecanismo para exportar estos datos a hojas de cálculo. Los administradores y el personal comercial necesitan exportar reportes en Excel respetando los filtros activos (fechas de registro, fechas de entrega, estado, cliente, modalidad de despacho y texto de búsqueda) para conciliar saldos financieros pendientes, coordinar la producción de pastelería y mantener auditoría operativa de las ventas y reservas.

## What Changes

- Creación de una clase de exportación con `Maatwebsite\Excel`: `App\Exports\OrdersExport`, con estilos corporativos (encabezado formal, formato numérico de moneda en Bs., alineaciones, anchos automáticos y fila de totales sumarios al pie).
- Integración de endpoint en backend: `GET /admin/orders/export-excel` en `OrderController::exportExcel` protegido por el middleware de permisos `permission:manage-orders`. El método aplica exactamente los mismos scopes de filtrado que la vista principal (`search`, `filterByRegisteredDate`, `filterByDeliveryDate`, `delivery_mode`, `customer_id`, `status`).
- Incorporación de botón de exportación en `resources/js/Pages/Admin/Orders/Index.vue` siguiendo las directrices de `frontend-design`:
  - Ubicado en la cabecera principal junto al botón de registro, con diseño sobrio y distinguible en modos claro y oscuro.
  - Indicador dinámico de descarga directa concatenando los parámetros de filtros activos.
  - Feedback visual intuitivo y accesible.

## Capabilities

### New Capabilities
- `orders/excel-export`: Exportación filtrada a Excel (.xlsx) del historial de pedidos y reservas de clientes, incluyendo metadatos del pedido, cliente, usuario registrador, resumen de productos, modalidad de entrega, totales financieros (total, monto pagado, saldo pendiente) y estados.

### Modified Capabilities
<!-- No se modifican los requerimientos previos del ciclo de vida básico de pedidos -->

## Impact

- **Backend:** Nueva clase `app/Exports/OrdersExport.php`, nuevo método `exportExcel(Request $request)` en `app/Http/Controllers/Admin/OrderController.php`, nueva ruta en `routes/admin.php`.
- **Frontend:** Actualización de `resources/js/Pages/Admin/Orders/Index.vue` para incluir el botón de exportación y la función de construcción de URL con parámetros de filtrado activos.
- **Dependencias:** Utiliza `maatwebsite/excel` (versión 3.1.67 ya instalada).
