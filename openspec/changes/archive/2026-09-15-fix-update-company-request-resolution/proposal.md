## Why

En el sistema actual no existe un contenedor multitenancy registrado como `app('tenant')`. Al intentar actualizar la información de la empresa desde el panel de administración (`POST /admin/company`), se dispara una excepción `BindingResolutionException: Target class [tenant] does not exist` en `UpdateCompanyRequest.php`, impidiendo guardar los cambios de la empresa.

## What Changes

- Corregir `UpdateCompanyRequest.php` para resolver la empresa mediante `CompanyFacade::getCompany()` en lugar del inexistente `app('tenant')->resolve()`.
- Validar adecuadamente la comparación del método de inventario (`inventory_method`) asegurando el soporte para enums y la verificación de `has_inventory_movements`.

## Capabilities

### New Capabilities
<!-- None -->

### Modified Capabilities
- `company-settings-update`: Asegura que la actualización de la configuración de empresa se ejecute sin dependencias de `tenant`, utilizando la fachada estándar `CompanyFacade`.

## Impact

- **Backend**: `app/Http/Requests/Admin/UpdateCompanyRequest.php`.
- **Funcionalidad**: Se restaura la capacidad de actualizar la configuración de la empresa (NIT, Razón Social, Método de Inventario, Logo, etc.) desde la ruta `/admin/company`.
