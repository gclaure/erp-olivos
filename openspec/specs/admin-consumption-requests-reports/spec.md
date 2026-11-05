# admin-consumption-requests-reports Specification

## Purpose

Proporciona filtrado por área solicitante, paginación interactiva con selector de cantidad por página (`per_page`) y exportación de reportes generales y específicos por solicitud en formatos Excel (`.xlsx`) y PDF dentro de la vista `/admin/consumption-requests`, restringido exclusivamente a usuarios con rol `ADMIN` o `Super Admin`, mostrando `Costo Unitario (Bs.)` por producto y `Total (Bs.)` consolidado.

## Requirements

### Requirement: Filtro por Área en Solicitudes de Consumo Interno
El sistema SHALL permitir filtrar el listado de solicitudes de consumo interno en `/admin/consumption-requests` según el área solicitante (`requested_by`).

#### Scenario: Filtrar solicitudes de consumo por área
- **WHEN** un usuario selecciona un área en el filtro de `/admin/consumption-requests` (ej. `Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción`, `Despacho`)
- **THEN** el listado de solicitudes muestra únicamente las solicitudes cuyo campo `requested_by` coincide con el área seleccionada

### Requirement: Restricción de exportación exclusiva para roles ADMIN y Super Admin en la vista principal
El sistema SHALL mostrar los botones de exportación (Excel y PDF, generales e individuales) únicamente en la vista `/admin/consumption-requests` y exclusivamente a usuarios con rol `ADMIN` (`Admin`, `Administrador`) o `Super Admin`, y SHALL bloquear con código HTTP `403` cualquier intento de acceso directo a las rutas de exportación por parte de otros roles. No se agregarán botones de exportación en la vista de detalle `/admin/consumption-requests/{id}`.

#### Scenario: Usuario ADMIN o Super Admin visualiza botones de exportación en `/admin/consumption-requests`
- **WHEN** un usuario autenticado con rol `ADMIN` o `Super Admin` ingresa a `/admin/consumption-requests`
- **THEN** visualiza los botones `Excel General` y `PDF General` en la cabecera y los botones `Excel` y `PDF` en cada fila o tarjeta de solicitud

#### Scenario: Usuario sin rol ADMIN ni Super Admin intenta exportar
- **WHEN** un usuario sin rol `ADMIN` ni `Super Admin` accede a `/admin/consumption-requests` o invoca directamente los endpoints de exportación
- **THEN** no visualiza los botones de exportación en la interfaz y recibe una respuesta `403 Forbidden` en los endpoints de exportación

### Requirement: Exportación de Reporte General de Solicitudes de Consumo en Excel y PDF
El sistema SHALL generar un reporte general en formato Excel (`.xlsx`) y en formato PDF de las solicitudes de consumo interno que coincidan con los filtros activos (`search`, `status`, `area`), detallando cada ítem con su `Costo Unitario (Bs.)` y un resumen/pie con `Total (Bs.)` sin incluir columnas duplicadas de costo ni columna de notas/observaciones.

#### Scenario: Descarga de reporte general filtrado por área en Excel y PDF
- **WHEN** un usuario `ADMIN` o `Super Admin` aplica un filtro de área en `/admin/consumption-requests` y presiona `Excel General` o `PDF General` (con un total de hasta 5,000 ítems para PDF)
- **THEN** el sistema descarga el archivo `.xlsx` o `.pdf` con todas las líneas de ítems de las solicitudes filtradas, mostrando `Costo Unitario (Bs.)` por ítem y `Total (Bs.)` en el totalizador

### Requirement: Límite de 5,000 registros y procesamiento por lotes en el Reporte PDF General
El sistema SHALL validar que la consulta filtrada para el Reporte PDF General (`/admin/consumption-requests/export-pdf`) no supere un máximo de **5,000 registros** antes de generar el documento, y SHALL procesar la extracción de datos en lotes (`chunk`) de objetos ligeros y renderizado por lotes sobre un lienzo compartido sin incrementar el límite de memoria de PHP.

#### Scenario: Bloqueo cuando el PDF General supera los 5,000 registros
- **WHEN** un usuario `ADMIN` o `Super Admin` solicita exportar el `PDF General` y los filtros actuales arrojan más de 5,000 registros de detalle
- **THEN** el sistema detiene la generación del PDF y muestra un mensaje indicando que el máximo permitido es de 5,000 registros y que debe filtrar por área, estado o rango de fechas solo lo necesario

#### Scenario: Generación en lotes cuando el PDF General tiene hasta 5,000 registros
- **WHEN** un usuario `ADMIN` o `Super Admin` solicita exportar el `PDF General` y el total de registros es menor o igual a 5,000
- **THEN** el sistema procesa los registros en lotes (`chunk(500)`), renderiza el PDF en lotes sobre un lienzo compartido liberando los nodos de DomPDF de cada lote y descarga el archivo PDF correctamente

### Requirement: Exportación de Reporte Específico por Solicitud de Consumo en Excel y PDF
El sistema SHALL generar un reporte específico en formato Excel (`.xlsx`) y en formato PDF para una solicitud de consumo individual desde la fila correspondiente en `/admin/consumption-requests`, incluyendo los datos de cabecera de la solicitud, el listado de ítems con `Costo Unitario (Bs.)` y el `Total (Bs.)` de la solicitud.

#### Scenario: Descarga de reporte individual de una solicitud en Excel o PDF
- **WHEN** un usuario `ADMIN` o `Super Admin` hace clic en el botón `Excel` o `PDF` de una solicitud específica en `/admin/consumption-requests`
- **THEN** el sistema descarga el archivo `.xlsx` o `.pdf` correspondiente a esa solicitud con sus metadatos, detalle de productos, `Costo Unitario (Bs.)` y `Total (Bs.)`

### Requirement: Paginador completo con navegación rápida y selector de cantidad por página en `/admin/consumption-requests`
El sistema SHALL mostrar en el pie del listado `/admin/consumption-requests` un paginador interactivo con el texto `Mostrando del {from} al {to} total de {total}`, botones de navegación (`«` primera página, `‹` página anterior, números de página numerados, `›` página siguiente, `»` última página) y un selector desplegable de registros por página (`10`, `25`, `50`, `100`, con `10` por defecto).

#### Scenario: Navegación entre páginas y cambio de cantidad por página
- **WHEN** el usuario visualiza el listado en `/admin/consumption-requests` y selecciona una página numerada, los botones `«`, `‹`, `›`, `»`, o cambia el selector de cantidad por página (`10`, `25`, `50`, `100`)
- **THEN** la tabla se actualiza mediante Inertia conservando los filtros activos (`search`, `status`, `area`, `start_date`, `end_date`, `order`) y mostrando el rango actualizado en `Mostrando del X al Y total de Z`

