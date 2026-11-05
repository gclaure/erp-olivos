# Design: Reportes Excel y PDF para Solicitudes de Consumo Interno

## Context

El módulo de Solicitudes de Consumo Interno (`/admin/consumption-requests`) gestiona los pedidos de insumos realizados por áreas operativas (`Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción`, `Despacho`). El área solicitante ya se almacena en la columna `requested_by` del modelo `ConsumptionRequest`, y cada línea de detalle (`ConsumptionRequestDetail`) referencia un `Product` que cuenta con `price` (costo unitario base) y registros `Stock` por almacén (`average_cost`). Ver `proposal.md` para la motivación completa.

## Goals / Non-Goals

**Goals:**
- Permitir filtrar las solicitudes en `/admin/consumption-requests` por área (`requested_by`) además de búsqueda y estado.
- Proveer exportación general (filtrada) y específica (por solicitud individual) tanto en Excel (`Maatwebsite\Excel`) como en PDF (`Barryvdh\DomPDF`).
- Restringir tanto la visibilidad de botones en UI como el acceso en backend exclusivamente a los roles `ADMIN` y `Super Admin`.
- Mostrar únicamente `Costo Unitario (Bs.)` por ítem y `Total (Bs.)` en el pie/resumen (sin columnas redundantes de costo ni columna de notas/observaciones).

**Non-Goals:**
- No agregar botones de exportación en la vista de detalle `/admin/consumption-requests/{id}` (`Show.vue`).
- No modificar el flujo de aprobación, despacho ni recepción de las solicitudes de consumo.

## Decisions

1. **Origen del Filtro por Área (`requested_by`)**:
   - *Decisión*: Combinar la lista estándar de áreas (`Cocina`, `Pastelería`, `Panadería`, `Eventos`, `Producción`, `Despacho`) con los valores distintos existentes en `ConsumptionRequest::distinct()->pluck('requested_by')`.
   - *Racional*: Garantiza que todas las áreas estándar aparezcan siempre en el selector y que cualquier área histórica personalizada también sea filtrable sin requerir migraciones adicionales.

2. **Resolución del Costo Unitario (`Costo Unitario (Bs.)`)**:
   - *Decisión*: Obtener el costo unitario desde `(float) $product->price`; si es `0`, usar como fallback el `average_cost` del registro `Stock` correspondiente al `warehouse_id` de la solicitud. El total (`Total (Bs.)`) suma los costos unitarios de los ítems listados.
   - *Racional*: Evita mostrar costos en cero cuando el producto tiene costo registrado en el maestro de productos o en el stock del almacén origen, manteniendo consistencia entre el reporte Excel y el PDF.

3. **Autorización para `ADMIN` y `Super Admin`**:
   - *Decisión*: En el controlador (`ConsumptionRequestController::checkIsAdmin()`), validar `hasAnyRole(['Admin', 'admin', 'Administrador', 'administrador', 'Super-admin', 'Super Admin', 'Super Administrador', 'super administrador', 'super-admin', 'super_admin', 'super admin'])` o `is_super_admin === true`, pasando el booleano `isAdmin` como prop a `Index.vue` y respaldándolo en el frontend con `canExport`.
   - *Racional*: Soporta todas las variantes de nombre de rol configuradas en Spatie Permission dentro del ERP y protege los endpoints mediante `abort(403)`.

4. **URLs Directas en los Enlaces de Descarga**:
   - *Decisión*: Usar rutas relativas directas (`/admin/consumption-requests/export-excel`, `/admin/consumption-requests/${req.id}/export-excel`, etc.) en etiquetas `<a target="_blank">` dentro de `Index.vue`.
   - *Racional*: Evita errores de Ziggy en tiempo de ejecución cuando la tabla de rutas en memoria del navegador no ha sido recargada.

5. **Procesamiento por Lotes (`chunk(500)`) y Límite de 5,000 Registros en PDF General**:
   - *Decisión*: En `ConsumptionRequestController::exportPdf()`, evaluar primero `$totalCount = (clone $query)->count()`. Si `$totalCount > 5000`, redirigir de vuelta (o responder con error 422 si se consulta por AJAX) indicando que debe filtrar solo lo necesario (máximo 5,000 registros). Si `$totalCount <= 5000`, iterar mediante `$query->chunk(500, ...)` resolviendo el costo con una subconsulta SQL (`addSelect` de `stocks.average_cost`) en lugar de hidratar la colección `product.stocks`, transformando cada fila en un objeto plano ligero (`stdClass`), y renderizando el PDF en lotes de 30 filas sobre una instancia compartida de `\Dompdf\Adapter\CPDF` liberando los nodos `Frame` y `FrameDecorator` tras cada lote.
   - *Racional*: Evita agotar la memoria RAM de PHP (128 MB) sin recurrir a `ini_set('memory_limit', ...)` y garantiza tiempos de respuesta predecibles en DomPDF.

6. **Paginador Completo con Selector `per_page` (`10`, `25`, `50`, `100`)**:
   - *Decisión*: En `ConsumptionRequestController::index()`, leer el parámetro `per_page` validado contra la lista permitida `[10, 25, 50, 100]` (por defecto `10`) y devolverlo en `filters.per_page`. En `Index.vue`, renderizar una barra de paginación con `Mostrando del {from} al {to} total de {total}`, controles `«`, `‹`, números de página (con ventana compacta y elipsis `...`), `›`, `»` y un `<select>` para `per_page` sincronizado con `applyFilters`.
   - *Racional*: Brinda navegación directa a primera/última página, salto a páginas específicas y control del volumen de filas mostradas en pantalla manteniendo los filtros activos.

## Risks / Trade-offs

- **[Risk] Volúmenes altos de detalles en el reporte general PDF** → **Mitigación**: Límite estricto de 5,000 registros con alerta amigable en UI + lectura por lotes (`chunk(500)`) de objetos planos sin hidratar colecciones pesadas.

