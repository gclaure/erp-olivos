## Why

El módulo actual "Reportes BI / Dashboard de Business Intelligence" muestra métricas abstractas y gráficos de ventas genéricos que no se adaptan a las necesidades operativas de la empresa (gestión de insumos, solicitudes de consumo y almacén). Los usuarios operativos y almaceneros necesitan reportes claros, directos y exportables en PDF y Excel para:
1. Auditar y controlar los consumos realizados por cada solicitante/consumidor por periodos o rangos de fechas.
2. Supervisar los movimientos de entradas y salidas de almacén de forma mensual o por rango de fechas.
3. Consultar el estado real del stock de insumos y productos, diferenciando entre productos con existencias (`stock > 0`) y el catálogo completo (incluyendo stock en cero).

## What Changes

- **Eliminación del Dashboard BI**: Se retiran los gráficos y métricas de Business Intelligence orientados a ventas abstractas de la sección de reportes.
- **Renombramiento del Módulo**: Se actualiza el elemento en el menú de navegación lateral (`SidebarService.php`) de "Reportes BI" a "Reportes" con icono de documento/reporte.
- **Reporte de Consumos por Consumidor**:
  - Filtros por consumidor (usuario solicitante), rango de fechas (selector de periodo mensual o personalizado), sucursal/almacén y estado de solicitud.
  - Tabla interactiva con resumen y detalle de consumos.
  - Exportación directa a PDF y Excel (.xlsx).
- **Reporte de Entradas y Salidas Mensuales (Almacenero)**:
  - Visualización y agrupación mensual de movimientos de kardex/inventario (compras, ajustes, despachos de consumos, mermas).
  - Filtros por periodo (mes/año o rango de fechas), almacén y tipo de movimiento.
  - Exportación directa a PDF y Excel (.xlsx).
- **Reporte de Stock de Insumos / Inventario (Almacenero)**:
  - Filtro para alternar entre "Solo productos con stock mayor a cero" (`stock > 0`) y "Todos los productos" (incluyendo stock 0).
  - Filtros adicionales por almacén, categoría y búsqueda textual.
  - Exportación directa a PDF y Excel (.xlsx).
- **Controladores y Servicios de Exportación**: Nuevas clases de exportación (`Maatwebsite\Excel`) y plantillas Blade para descarga de PDF con `Barryvdh\DomPDF\Facade\Pdf`.

## Capabilities

### New Capabilities
- `operational-reports`: Interfaz unificada por pestañas (Tabs) y endpoints de consulta y exportación (PDF y Excel) para consumos por consumidor, entradas/salidas mensuales y stock de insumos.

### Modified Capabilities
<!-- None -->

## Impact

- **Backend**:
  - Actualización de `app/Http/Controllers/Admin/ReportController.php` para gestionar los filtros de los 3 reportes y los endpoints de descarga PDF/Excel.
  - Creación de clases de exportación en `app/Exports/` (ej. `ConsumerConsumptionsExport`, `MonthlyMovementsExport`, `InventoryStockExport`).
  - Creación de vistas Blade para PDFs en `resources/views/exports/reports/`.
  - Actualización de `app/Services/SidebarService.php`.
- **Frontend**:
  - Rediseño de `resources/js/Pages/Admin/Reports/Index.vue` con arquitectura de pestañas responsivas (Tailwind CSS 3, Vue 3 Composition API).
- **Dependencias**:
  - Utiliza las librerías ya instaladas `barryvdh/laravel-dompdf` y `maatwebsite/excel`.
