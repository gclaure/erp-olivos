## Context

El comprobante PDF emitido desde `ConsumptionRequestController::print()` se renderiza mediante `Barryvdh\DomPDF\Facade\Pdf` a partir de la vista `resources/views/admin/consumption-requests/receipt.blade.php`. DomPDF requiere CSS 2.1 estructurado con tablas HTML, unidades físicas (`pt`, `mm`, `%`) y fuentes estándar para evitar problemas de compatibilidad gráfica y corte de saltos de página.

## Goals / Non-Goals

**Goals:**
- Presentar un diseño editorial de nivel corporativo para la Solicitud y Acta de Recepción de Consumo Interno.
- Mostrar la triple comparación de cantidades:
  - **Solicitado**: `$detail->quantity_requested`
  - **Despachado**: `$detail->quantity_delivered` (o `0.00` si está pendiente)
  - **Recibido**: `$detail->quantity_received` (o `Pendiente` si aún no se recepciona)
- Mostrar observaciones de discrepancia (`$detail->receive_observation` o `$detail->observation`) bajo el nombre del producto en caso de diferencias.
- Incluir un bloque de trazabilidad de ciclo de vida:
  - **Creado:** `$request->user->name` y `$request->created_at`
  - **Aprobado:** `$request->approvedByUser?->name` y `$request->approved_at`
  - **Despachado:** `$request->dispatchedByUser?->name` y `$request->dispatched_at`
  - **Recepcionado:** `$request->receivedByUser?->name` y `$request->received_at`
- Tres cajas de firma al pie: Solicitante (Consumidor), Despachador (Almacén), Receptor Conforme (Área Operativa).
- Cargar las relaciones en `ConsumptionRequestController::print()`.

**Non-Goals:**
- No modificar el esquema de base de datos ni cambiar endpoints adicionales.

## Decisions

### 1. Sistema de Tokens Visuales para DomPDF
- **Paleta**:
  - Encabezados y Títulos: `#0f172a` (Slate 900)
  - Textos secundarios y metadatos: `#475569` (Slate 600)
  - Líneas y bordes divisorios: `#e2e8f0` (Slate 200)
  - Fondos de encabezados de tabla y cards: `#f8fafc` (Slate 50)
  - Acento Estado Aprobado / Entregado: `#047857` (Emerald 700) sobre `#ecfdf5`
  - Acento Estado Despachado: `#6d28d9` (Purple 700) sobre `#faf5ff`
  - Acento Estado Pendiente / Observado / Discrepancia: `#b45309` (Amber 700) sobre `#fffbeb`
- **Tipografía**:
  - Fuente principal: `'Helvetica', 'Arial', sans-serif`
  - Tamaños: Título principal (15pt), subtítulos y códigos (10pt), cuerpo de tabla (8.5pt), notas y pie (7.5pt).

### 2. Carga Eager Loading en Controlador
```php
$consumptionRequest->load([
    'warehouse.branch.company',
    'user',
    'approvedByUser',
    'dispatchedByUser',
    'receivedByUser',
    'details.product.unitOfMeasure'
]);
```
