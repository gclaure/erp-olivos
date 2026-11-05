# Tasks: Reportes Excel y PDF para Solicitudes de Consumo Interno

## 1. Backend: Rutas y Controlador (`ConsumptionRequestController`)

- [x] 1.1 Registrar las rutas `export-excel`, `export-pdf`, `{consumption_request}/export-excel` y `{consumption_request}/export-pdf` dentro del grupo `consumption-requests.` en `routes/admin.php` y verificar que estén antes de las rutas dinámicas `{consumption_request}`.
- [x] 1.2 Actualizar `ConsumptionRequestController@index` para soportar el filtro `area` (`requested_by`), enviar la prop `areas` (áreas estándar + distintas en BD) y la prop `isAdmin` para roles `ADMIN` y `Super Admin`, verificando la respuesta Inertia.
- [x] 1.3 Implementar en `ConsumptionRequestController` los métodos `exportExcel`, `exportPdf`, `exportSingleExcel`, `exportSinglePdf` y `ensureAdminAccess` (restringiendo con `403` a roles distintos de `ADMIN` y `Super Admin`).

## 2. Exportaciones Excel (`Maatwebsite\Excel`)

- [x] 2.1 Crear `app/Exports/ConsumptionRequestsGeneralExport.php` con filtro por `search`, `status` y `area`, incluyendo `Costo Unitario (Bs.)` por ítem y fila final `TOTAL GENERAL` (`Total (Bs.)`), sin columnas redundantes ni observaciones.
- [x] 2.2 Crear `app/Exports/ConsumptionRequestSingleExport.php` para una solicitud individual con bloque de cabecera de la solicitud, tabla de ítems con `Costo Unitario (Bs.)` y fila `TOTAL DE LA SOLICITUD (Bs.)`.

## 3. Plantillas PDF (`Barryvdh\DomPDF`)

- [x] 3.1 Crear `resources/views/exports/reports/consumption-requests-general-pdf.blade.php` (formato A4 horizontal) con resumen de métricas (`Total (Bs.)`) y tabla detallada con `Costo Unitario (Bs.)`.
- [x] 3.2 Crear `resources/views/exports/reports/consumption-request-single-pdf.blade.php` (formato A4 vertical) con metadatos de la solicitud, tabla de productos con `Costo Unitario (Bs.)` y fila `TOTAL (BS.)`.

## 4. Frontend: Vista `/admin/consumption-requests` (`Index.vue`)

- [x] 4.1 Agregar el selector de filtro por área (`areaFilter`) en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` sincronizado con los parámetros de búsqueda de Inertia.
- [x] 4.2 Agregar en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` los botones `Excel General` y `PDF General` en la cabecera y los botones `Excel` y `PDF` por cada fila (tabla desktop y tarjetas móviles) visibles únicamente cuando `canExport` es verdadero (`ADMIN` o `Super Admin`), verificando con `npm run build` que el bundle compile sin errores y que no existan botones de exportación en `Show.vue`.

## 5. Optimización por Lotes (Batch/Chunk) y Límite de 5,000 en PDF General

- [x] 5.1 Actualizar `ConsumptionRequestController@exportPdf` para validar un máximo de 5,000 registros (retornando error claro cuando se supere) y procesar la extracción en lotes (`chunk(500)`) con subconsulta SQL para costo de stock y liberación de memoria (`gc_collect_cycles`), sin aumentar `memory_limit`.
- [x] 5.2 Adaptar `resources/views/exports/reports/consumption-requests-general-pdf.blade.php` para consumir directamente el arreglo plano de registros pre-procesados por lotes y el resumen calculado (`summary`), evitando cálculos pesados en la vista Blade.
- [x] 5.3 Actualizar el botón `PDF General` en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` para manejar la validación del límite de 5,000 registros mostrando una alerta con `SweetAlert2` cuando se exceda el límite y compilar con `npm run build`.

## 6. Paginador Completo con Selector `per_page` en `/admin/consumption-requests`

- [x] 6.1 Actualizar `ConsumptionRequestController@index` para recibir y validar el parámetro `per_page` (`10`, `25`, `50`, `100`, por defecto `10`), aplicarlo en `$query->paginate($perPage)->withQueryString()` y exponer `per_page` dentro de `filters`.
- [x] 6.2 Actualizar el bloque de paginación en `resources/js/Pages/Admin/ConsumptionRequest/Index.vue` con el texto `Mostrando del {from} al {to} total de {total}`, botones `«` (primera), `‹` (anterior), números de página interactivos, `›` (siguiente), `»` (última) y el selector desplegable de cantidad por página (`10`, `25`, `50`, `100`), compilando con `npm run build`.


