## 1. Backend Controller Updates

- [x] 1.1 Cargar relaciones de trazabilidad (`approvedByUser`, `dispatchedByUser`, `receivedByUser`) en `ConsumptionRequestController::print()`

## 2. Blade PDF Template Redesign

- [x] 2.1 Reestructurar `resources/views/admin/consumption-requests/receipt.blade.php` con diseño editorial premium (paleta Slate, tipografía jerárquica y estilos DomPDF limpios)
- [x] 2.2 Incorporar tabla comparativa de insumos con columnas `SOLICITADO`, `DESPACHADO`, `RECIBIDO`, `UNIDAD` y notas de discrepancia
- [x] 2.3 Agregar bloque de trazabilidad operativa completa y 3 cajas de firma de conformidad (Solicitante, Despacho Almacén, Recepción Destino)

## 3. Verification

- [x] 3.1 Probar la generación del PDF con `php artisan` / curl / navegador en `/admin/consumption-requests/{id}/print`
- [x] 3.2 Validar que no existan advertencias ni errores en el renderizado del PDF
