## Context

En `UpdateCompanyRequest.php`, el método `withValidator()` intentaba resolver el objeto de la empresa mediante `app('tenant')->resolve()`. Al no existir el servicio `tenant` en el Service Container de Laravel, se genera un error 500 `BindingResolutionException`.

## Goals / Non-Goals

**Goals:**
- Sustituir la llamada `app('tenant')->resolve()` por `CompanyFacade::getCompany()`.
- Validar de forma segura si existe la empresa antes de verificar la restricción de `has_inventory_movements` sobre `inventory_method`.
- Mantener compatibilidad total con la arquitectura basada en `CompanyFacade` y `CompanyService`.

**Non-Goals:**
- Modificar la estructura de la base de datos o el modelo `Company`.

## Decisions

### 1. Uso de `CompanyFacade::getCompany()`
- Se importa `App\Facades\CompanyFacade` en `UpdateCompanyRequest.php`.
- Se extrae el método de inventario actual soportando instancias de enum `InventoryMethod` o cadenas primitivas.

## Risks / Trade-offs

- Ningún riesgo detectado: `CompanyFacade` ya está completamente operativo y probado en los demás controladores del sistema.
