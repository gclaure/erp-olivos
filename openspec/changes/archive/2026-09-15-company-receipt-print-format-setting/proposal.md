## Why

El sistema cuenta con motores y plantillas de impresión tanto para formato estándar de página (`media` / carta) como para formato de ticket continuo (`rollo` / 80mm). Sin embargo, en la pantalla de Configuración de la Empresa no existe actualmente un control visual para que el administrador seleccione qué formato de impresión rige por defecto en el negocio. Permitir configurar esta preferencia desde `/admin/company` permite a los usuarios personalizar el comportamiento de impresión de ventas, proformas y solicitudes de forma centralizada.

## What Changes

- **Integración UI en `Company.vue`**:
  - Incorporar una sección de "Formato Predeterminado de Impresión" con selector interactivo de tarjetas (Radio Cards) entre "Media Página / Carta (PDF)" (`media`) y "Rollo / Ticket Térmico (80mm)" (`rollo`).
  - Integrar `receipt_type` en el `useForm` reactivo.
- **Soporte en Backend y Validación**:
  - Exponer `receipt_type` en `CompanyController::edit()`.
  - Validar `receipt_type => ['required', 'string', Rule::in(['media', 'rollo'])]` en `UpdateCompanyRequest.php`.
  - Asegurar que `CompanyService::update()` persista `receipt_type`.
- **Comportamiento en Controladores de Impresión**:
  - Usar `company->receipt_type` como valor fallback cuando no se envíe un query param específico en las solicitudes de impresión de comprobantes.

## Capabilities

### New Capabilities
- `company-receipt-format-configuration`: Configuración administrativa para definir el formato predeterminado de comprobantes e impresiones corporativas (`media` vs `rollo`).

### Modified Capabilities
- `company-settings-update`: Extensión del formulario de configuración de la empresa para incluir la gestión de `receipt_type`.

## Impact

- **Frontend**: `resources/js/Pages/Admin/Settings/Company.vue`.
- **Backend**: `app/Http/Controllers/Admin/CompanyController.php`, `app/Http/Requests/Admin/UpdateCompanyRequest.php`.
- **Modelos**: `app/Models/Company.php` (campo `receipt_type`).
