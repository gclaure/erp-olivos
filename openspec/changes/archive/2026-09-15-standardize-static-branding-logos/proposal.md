## Why

El sistema cuenta con un set definido y optimizado de logotipos oficiales en el frontend y assets públicos (`/img/logo-dark.png` y `/img/logo-light.png`). Mantener un componente de subida de archivos (Dropzone) en la vista de configuración de empresa agrega complejidad innecesaria, dependencias superfluas y riesgo de inconsistencia gráfica. Es necesario retirar la funcionalidad de subida de logo y fijar de manera canónica el uso de `logo-dark.png` para la interfaz oscura (sidebar) y `logo-light.png` para documentos impresos (PDFs, recibos) y previsualizaciones claras.

## What Changes

- **Eliminación del Dropzone de Logo**: Se retira el componente Dropzone, sus estilos CSS externos, instancias y referencias de carga en `Company.vue`.
- **Estandarización de Logos Estáticos**:
  - `AdminLayout.vue` utiliza canónicamente `/img/logo-dark.png` para la barra lateral.
  - Vistas de impresión y comprobantes (`receipt.blade.php` y futuros reportes) utilizan canónicamente `public_path('img/logo-light.png')` para superficies claras.
  - La tarjeta de previsualización en `Company.vue` muestra `/img/logo-light.png`.
- **Limpieza de Backend**: Se retira la validación de `logo` en `UpdateCompanyRequest.php` y se simplifica la gestión de datos en `CompanyService.php`.

## Capabilities

### New Capabilities
- `static-branding-standardization`: Reglas y convenciones para el uso de logotipos canónicos `logo-dark.png` (superficies oscuras) y `logo-light.png` (superficies claras e impresión) sin carga dinámica de archivos.

### Modified Capabilities
- `company-settings-update`: Se modifica la interfaz de configuración de empresa para enfocarse exclusivamente en metadatos y preferencias (NIT, Razón Social, Teléfono, Email, Cierre, Método de Inventario, Switch `show_name`), eliminando la subida de logo.

## Impact

- **Frontend**: `resources/js/Pages/Admin/Settings/Company.vue`, `resources/js/Layouts/AdminLayout.vue`.
- **Backend / Vistas Blade**: `app/Http/Requests/Admin/UpdateCompanyRequest.php`, `resources/views/admin/consumption-requests/receipt.blade.php`.
- **Dependencias**: Se puede prescindir de la inicialización de Dropzone en la configuración de la empresa.
