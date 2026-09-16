## 1. Backend: Validación y Exposición de receipt_type

- [x] 1.1 Incluir `receipt_type` en el array retornado por `CompanyController::edit()`
- [x] 1.2 Agregar la regla de validación de `receipt_type` (`required`, `Rule::in(['media', 'rollo'])`) en `app/Http/Requests/Admin/UpdateCompanyRequest.php`

## 2. Frontend: Selector de Formato en Company.vue

- [x] 2.1 Agregar `receipt_type` al `useForm` de `resources/js/Pages/Admin/Settings/Company.vue`
- [x] 2.2 Diseñar el bloque responsivo de Radio Cards para seleccionar entre "Media Página / Carta" (`media`) y "Rollo / Ticket Térmico" (`rollo`)
- [x] 2.3 Compilar assets con `npm run build` y verificar que el formulario se renderice y guarde correctamente

## 3. Integración en Controladores y Plantillas de Impresión

- [x] 3.1 Crear la plantilla `resources/views/admin/consumption-requests/receipt-roll.blade.php` para impresión continua en 80mm
- [x] 3.2 Actualizar `ConsumptionRequestController::print()` para respetar `$company->receipt_type` y renderizar en formato rollo dinámico
- [x] 3.3 Actualizar `SalePrintController` y `QuotationPrintController` para usar `$company->receipt_type` como valor por defecto
