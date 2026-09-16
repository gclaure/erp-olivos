## 1. Backend - Corrección de UpdateCompanyRequest

- [x] 1.1 Importar `CompanyFacade` en `UpdateCompanyRequest.php` y sustituir `app('tenant')->resolve()` por `CompanyFacade::getCompany()`.
- [x] 1.2 Proteger la comparación de `inventory_method` contra valores null o enums casteados.
- [x] 1.3 Validar sintaxis y probar el flujo de actualización de empresa desde `/admin/company`.
