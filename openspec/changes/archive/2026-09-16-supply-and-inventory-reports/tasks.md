## 1. Menú de Navegación y Rutas

- [x] 1.1 Actualizar `app/Services/SidebarService.php` para cambiar el texto "Reportes BI" a "Reportes", con icono de documento.
- [x] 1.2 Definir rutas en `routes/admin.php` para la visualización del índice y los endpoints de descarga PDF y Excel de cada reporte.

## 2. Backend y Clases de Exportación (Excel & PDF)

- [x] 2.1 Crear clases de exportación de Excel en `app/Exports/`:
  - `ConsumerConsumptionsExport.php` (Consumos por consumidor con filtros de fecha y estado).
  - `MonthlyMovementsExport.php` (Entradas y salidas mensuales con detalle y sumatorias).
  - `InventoryStockExport.php` (Stock de insumos con filtro stock > 0 o todos).
- [x] 2.2 Crear plantillas Blade para exportación a PDF en `resources/views/exports/reports/`:
  - `consumer-consumptions-pdf.blade.php`
  - `monthly-movements-pdf.blade.php`
  - `inventory-stock-pdf.blade.php`
- [x] 2.3 Refactorizar `app/Http/Controllers/Admin/ReportController.php` para manejar las consultas de datos para las 3 pestañas y los métodos de streaming/descarga de PDF y Excel.

## 3. Frontend - Interfaz de Reportes Operativos

- [x] 3.1 Rediseñar `resources/js/Pages/Admin/Reports/Index.vue` eliminando gráficos BI y creando un sistema de 3 pestañas (`Consumos por Consumidor`, `Entradas y Salidas Mensuales`, `Stock de Insumos`).
- [x] 3.2 Implementar barra de filtros responsiva con selectores de consumidor, fechas/meses, almacenes y switches de condición de stock (`stock > 0` vs `todos`).
- [x] 3.3 Implementar tablas interactivas con estados de carga, totales y botones visibles de exportación "Exportar PDF" y "Exportar Excel" para cada pestaña.
- [x] 3.4 Validar diseño responsivo (mobile, tablet, desktop) y compatibilidad con modo oscuro y tema visual de la aplicación.

## 4. Verificación y Pruebas

- [x] 4.1 Probar la consulta y filtrado en cada una de las 3 pestañas.
- [x] 4.2 Probar la descarga y formato de los archivos PDF generados.
- [x] 4.3 Probar la descarga y formato de los archivos Excel (`.xlsx`) generados.
- [x] 4.4 Ejecutar linters/pruebas para asegurar tipado estricto y código limpio.
