## 1. Frontend: Limpieza de Formulario y Template en Company.vue

- [x] 1.1 Remover los campos `inventory_method` e `inventories_closed_until` del objeto reactivo `form` en `resources/js/Pages/Admin/Settings/Company.vue`.
- [x] 1.2 Eliminar el bloque de UI "Configuración de Inventario (ERP-Grade)" (tarjeta/sección completa) en `resources/js/Pages/Admin/Settings/Company.vue`.
- [x] 1.3 Verificar que el layout, espaciados y tarjetas restantes en `Company.vue` se muestren balanceados y responsivos.

## 2. Backend: Ajuste de Validación en UpdateCompanyRequest

- [x] 2.1 Modificar `app/Http/Requests/Admin/UpdateCompanyRequest.php` para eliminar o marcar como opcionales (`nullable`) las reglas de `inventory_method` e `inventories_closed_until`.
- [x] 2.2 Verificar `CompanyController.php` y `CompanyService.php` para asegurar que el guardado de la configuración funcione correctamente sin estos parámetros en el payload.

## 3. Verificación

- [x] 3.1 Probar la carga de `/admin/company` y el guardado de cambios (nombre, nit, show_name, receipt_type).
- [x] 3.2 Confirmar que no haya advertencias o errores en la consola y que los tests o controladores respondan con éxito.
