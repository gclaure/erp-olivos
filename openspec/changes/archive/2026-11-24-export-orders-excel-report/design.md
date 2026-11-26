# Design

## Context

El sistema gestiona pedidos y reservas de pastelería mediante el modelo `Order` con relaciones a `Customer`, `User`, `OrderItem`, `OrderPayment` y scopes de consulta en PostgreSQL (`search`, `filterByRegisteredDate`, `filterByDeliveryDate`, `registered`, `cancelled`). El proyecto ya cuenta con el paquete `maatwebsite/excel` (3.1.67) implementado con éxito en otros módulos (por ejemplo `SalesExport`, `MonthlyMovementsExport`).

## Goals / Non-Goals

**Goals:**
- Generar un archivo Excel con formato limpio, profesional y legible que represente fielmente la consulta activa del usuario en `/admin/orders`.
- Mantener paridad exacta entre los filtros del frontend (Inertia/Vue 3) y la consulta ejecutada por la exportación en Laravel.
- Proporcionar una fila final de sumatorias para columnas clave (Total, Monto Pagado, Saldo Pendiente).
- Integrar el botón de exportación en la interfaz con diseño distinguido (`frontend-design`), respetando responsive y soporte para modo oscuro.

**Non-Goals:**
- No se implementa exportación asíncrona por colas (queues) dado el volumen actual de pedidos; se realiza en streaming/descarga directa síncrona.
- No se modifica la estructura ni los datos de la base de datos (no requiere nuevas migraciones).

## Decisions

### 1. Reutilización de Scopes de Consulta en `OrdersExport`
- **Decisión:** `OrdersExport` recibirá la instancia base de `$query` ya filtrada construida en `OrderController` o construirá la consulta reutilizando exactamente los mismos métodos scopes del modelo `Order`.
- **Alternativa descartada:** Duplicar la lógica de filtrado o filtrar colecciones en memoria, lo cual deterioraría el rendimiento.

### 2. Estructura de Exportación con `Maatwebsite\Excel`
- **Decisión:** Implementar las interfaces `FromQuery`, `WithHeadings`, `WithMapping`, `WithStyles`, `ShouldAutoSize`, `WithEvents` y `WithChunkReading`.
  - Mapeo de columnas:
    1. Folio (#00001)
    2. Fecha Registro (d/m/Y)
    3. Registrado Por (Nombre del usuario o "Sistema")
    4. Cliente (Nombre completo)
    5. Celular / Teléfono
    6. Fecha Entrega (d/m/Y)
    7. Hora Entrega (H:i)
    8. Modalidad (Retiro en tienda / Entrega a domicilio)
    9. Dirección de Entrega
    10. Productos Solicitados (Resumen formateado: ej. "1 Torta 3 Leches (Mediana), 2 Pie")
    11. Subtotal (Bs.)
    12. Descuento (Bs.)
    13. Total (Bs.)
    14. Monto Pagado (Bs.)
    15. Saldo Pendiente (Bs.)
    16. Estado (REGISTRADO / ANULADO)
- **Totales en pie:** Mediante el evento `AfterSheet`, se totalizan `Total`, `Monto Pagado` y `Saldo Pendiente` con fondo destacado y formato monetario `#,##0.00`.

### 3. Componente y Estilo de Botón en Frontend (`frontend-design`)
- **Decisión:** Colocar el botón de exportación en el Header de `resources/js/Pages/Admin/Orders/Index.vue` junto al botón de registro:
  - Diseño: botón con estilo refinado, fondo blanco/zinc-50 con borde sutil en modo claro (`border border-zinc-200 hover:bg-zinc-100 text-zinc-700`), fondo `secondary-800` en modo oscuro (`dark:border-secondary-700 dark:hover:bg-secondary-700 dark:text-secondary-100`).
  - Icono distintivo: `table_view` con acento verde esmeralda (`text-emerald-600 dark:text-emerald-400`).
  - Acción: método `exportExcel()` que serializa `localFilters` (omitiendo `page` y `per_page`) y redirige hacia `route('admin.orders.export-excel', queryParams)`.

## Risks / Trade-offs

- **[Filtros de fecha vacíos o amplios]** → El uso de `FromQuery` con `WithChunkReading` procesa la consulta en trozos optimizados sin agotar la memoria de PHP.
- **[Caracteres especiales en nombres de clientes o productos]** → PhpSpreadsheet codifica en UTF-8 de forma nativa para evitar caracteres corruptos.
