## Context

El sistema cuenta actualmente con un controlador `ReportController.php` y una vista `Reports/Index.vue` orientados a métricas abstractas de Business Intelligence (ventas, márgenes, gráficos Chart.js). Sin embargo, el core del negocio opera en base a insumos, solicitudes de consumo y movimientos de almacén gestionados a través de `ConsumptionRequest`, `ConsumptionRequestDetail`, `Kardex` y existencias en `products` / `warehouses`.

## Goals / Non-Goals

**Goals:**
- Restructurar el módulo de reportes hacia tres pestañas especializadas:
  1. `Consumos por Consumidor`: Auditoría de solicitudes y artículos despachados por usuario.
  2. `Entradas y Salidas Mensuales`: Flujo de almacén (entradas vs salidas) con totales y detalle agrupado.
  3. `Stock de Insumos`: Existencias en almacenes con filtro booleano "Solo con stock > 0" vs "Todos los productos".
- Proveer botones de exportación instantánea a **PDF** (usando `barryvdh/laravel-dompdf` con plantillas Blade limpias y membretadas) y **Excel** (usando `maatwebsite/excel`).
- Actualizar `SidebarService.php` renombrando "Reportes BI" a "Reportes" con icono de documento.

**Non-Goals:**
- No se mantendrán gráficos de ventas hipotéticas ni dashboards de BI heredados.
- No se alterará la estructura de base de datos ni migraciones de tablas existentes.

## Decisions

1. **Arquitectura de Vistas en Vue 3**:
   - Mantener una vista principal unificada `Admin/Reports/Index.vue` con navegación por pestañas (`activeTab = 'consumptions' | 'movements' | 'stock'`), control de estado reactivo y preservación de filtros vía Inertia router / query params (`preserveState: true`).
2. **Endpoints de Exportación Dedicados**:
   - `admin.reports.consumptions.pdf` & `admin.reports.consumptions.excel`
   - `admin.reports.movements.pdf` & `admin.reports.movements.excel`
   - `admin.reports.stock.pdf` & `admin.reports.stock.excel`
   - Permite descargar directamente con las mismas queries y filtros pasados como query params.
3. **Clases de Exportación Excel**:
   - Implementar `ConsumerConsumptionsExport`, `MonthlyMovementsExport` y `InventoryStockExport` implementando `FromCollection` / `FromQuery`, `WithHeadings`, `WithStyles`, `ShouldAutoSize`.
4. **Vistas Blade para PDF**:
   - Ubicadas en `resources/views/exports/reports/` (`consumer-consumptions-pdf.blade.php`, `monthly-movements-pdf.blade.php`, `inventory-stock-pdf.blade.php`) con diseño corporativo, paginación, fecha de emisión y totales.

## Risks / Trade-offs

- **[Riesgo]** Gran volumen de datos en exportaciones de Kardex o Consumos de rangos de fechas amplios.
  → **Mitigación**: Aplicar límites por defecto a rangos mensuales o paginación en UI y optimización de queries con `select()`, eager loading (`with('consumer', 'warehouse', 'product')`) e índices de base de datos existentes.
- **[Riesgo]** Renderizado de PDF con DomPDF ante muchas páginas.
  → **Mitigación**: Estilos CSS inline optimizados, tablas compactas y soporte de salto de página limpio (`page-break-inside: avoid`).
