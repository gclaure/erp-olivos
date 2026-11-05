# Proposal: Reportes Excel y PDF para Solicitudes de Consumo Interno (Admin / Super Admin)

## Why

Los usuarios con rol de Administrador (`ADMIN` y `Super Admin`) necesitan auditar y exportar las Solicitudes de Consumo Interno en la vista `/admin/consumption-requests` tanto de manera general (filtrando por área solicitante, estado y búsqueda) como de manera específica por cada solicitud individual, en formatos Excel (`.xlsx`) y PDF, visualizando el costo unitario (`Costo Unitario (Bs.)`) de cada producto y el total consolidado (`Total (Bs.)`).

## What Changes

- **Filtro por Área Solicitante**: Se incorpora el filtro `area` en la vista `/admin/consumption-requests` (`ConsumptionRequestController@index` e `Index.vue`) basado en el campo `requested_by` de `ConsumptionRequest` y el catálogo de áreas (`Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción`, `Despacho`).
- **Reporte General (Excel y PDF)**: Se agregan los endpoints `GET /admin/consumption-requests/export-excel` y `GET /admin/consumption-requests/export-pdf` que respetan los filtros activos (`search`, `status`, `area`, `start_date`, `end_date`) y listan el detalle de ítems con `Costo Unitario (Bs.)` y `Total (Bs.)` en el resumen/pie de tabla.
- **Procesamiento en Lote (Batch/Chunk) y Límite de 5,000 Registros en PDF General**: El endpoint `GET /admin/consumption-requests/export-pdf` valida previamente que el total de registros no supere los **5,000 ítems**; si los supera, solicita al usuario aplicar filtros (área, estado o fechas) para exportar solo lo necesario. Cuando el volumen es `<= 5,000`, procesa los registros por lotes (`chunk(500)`) construyendo estructuras planas ligeras (`stdClass`) y liberando memoria Eloquent en cada lote sin requerir incremento de `memory_limit`.
- **Reporte Específico por Solicitud (Excel y PDF)**: Se agregan los endpoints `GET /admin/consumption-requests/{consumption_request}/export-excel` y `GET /admin/consumption-requests/{consumption_request}/export-pdf` para exportar el detalle de una solicitud individual con sus metadatos de cabecera, costos unitarios y `Total (Bs.)`.
- **Botones de Exportación exclusivos en `/admin/consumption-requests`**: Se muestran los botones de exportación general en la cabecera y por fila en la tabla/tarjetas de `/admin/consumption-requests` únicamente para usuarios con rol `ADMIN` o `Super Admin`. No se agregan botones adicionales en la vista de detalle `/admin/consumption-requests/{id}`.
- **Paginador Completo con Selector de Cantidad por Página (`per_page`)**: Se reemplaza el paginador básico de `/admin/consumption-requests` por un paginador completo estilo tabla administrativa que incluye el texto `Mostrando del {from} al {to} total de {total}`, botones de navegación (`«` primera, `‹` anterior, números de página con ventana deslizante, `›` siguiente, `»` última) y selector desplegable de cantidad por página (`10`, `25`, `50`, `100`, con `10` por defecto).

## Capabilities

### New Capabilities
- `admin-consumption-requests-reports`: Filtro por área y exportación de reportes generales e individuales en Excel y PDF para Solicitudes de Consumo Interno (`/admin/consumption-requests`), exclusivo para roles `ADMIN` y `Super Admin`.

### Modified Capabilities
<!-- Ninguna capacidad existente cambia sus requisitos base -->

## Impact

- **Rutas**: `routes/admin.php` (4 rutas de exportación bajo el grupo `consumption-requests.`).
- **Controlador**: `app/Http/Controllers/Admin/ConsumptionRequestController.php` (`index`, `exportExcel`, `exportPdf`, `exportSingleExcel`, `exportSinglePdf`, `ensureAdminAccess`).
- **Clases de Exportación Excel**: `app/Exports/ConsumptionRequestsGeneralExport.php`, `app/Exports/ConsumptionRequestSingleExport.php`.
- **Plantillas PDF Blade**: `resources/views/exports/reports/consumption-requests-general-pdf.blade.php`, `resources/views/exports/reports/consumption-request-single-pdf.blade.php`.
- **Frontend Vue**: `resources/js/Pages/Admin/ConsumptionRequest/Index.vue`.

