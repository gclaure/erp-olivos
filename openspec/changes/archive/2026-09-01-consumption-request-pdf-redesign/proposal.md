## Why

El reporte PDF generado en `/admin/consumption-requests/{id}/print` actualmente solo muestra la cantidad solicitada inicial y posee un formato visual básico. En la práctica operativa de control de inventarios, es fundamental que el documento de control y acta de entrega refleje tanto las cantidades solicitadas como las efectivamente despachadas por almacén y las recibidas por el área operativa (Cocina, Pastelería, etc.), documentando observaciones de discrepancias y las firmas de conformidad correspondientes con un diseño editorial premium.

## What Changes

- Rediseñar integralmente la plantilla Blade del PDF (`receipt.blade.php`) aplicando principios de diseño editorial del skill `frontend-design`:
  - Tipografía limpia con jerarquía estricta, paleta Slate corporativa (`#0f172a`, `#334155`, `#475569`), acentos sutiles y bordes refinados compatibles con DomPDF.
  - Encabezado con logotipo corporativo nítido, datos fiscales de sucursal y badge estilizado del estado del documento.
  - Bloque estructurado de datos de solicitud y trazabilidad del ciclo de vida operativo (Solicitante, Aprobador, Despachador y Receptor con marcas de tiempo).
  - Tabla comparativa de insumos con columnas: `CÓDIGO`, `INSUMO / ESPECIFICACIONES`, `SOLICITADO`, `DESPACHADO`, `RECIBIDO`, `UNIDAD` y renderizado de notas de discrepancia/observación.
  - Bloque de 3 firmas de conformidad equilibradas: **1. Solicitante**, **2. Despachado por Almacén**, **3. Recepción Conforme en Destino**.
  - Pie de página institucional con numeración y metadatos de auditoría.
- Cargar en `ConsumptionRequestController::print()` las relaciones necesarias para mostrar la trazabilidad completa (`user`, `approvedByUser`, `dispatchedByUser`, `receivedByUser`, etc.).

## Capabilities

### New Capabilities
- `consumption-request-pdf-redesign`: Generación de comprobante PDF oficial de solicitud y recepción de consumo interno con trazabilidad de ciclo y diseño editorial premium.

### Modified Capabilities

## Impact

- Backend: `ConsumptionRequestController.php` (método `print`).
- Vistas Blade: `resources/views/admin/consumption-requests/receipt.blade.php`.
