## Why

La configuración de empresa en `/admin/company` incluye el switch "Mostrar nombre en el menú" (`show_name`), pero actualmente no tiene efecto real:
1. En `HandleInertiaRequests.php`, el valor `'show_name' => true` está fijado de forma estática.
2. En `AdminLayout.vue`, la barra lateral solo muestra la imagen del logotipo y no evalúa `show_name` para exhibir el nombre de la empresa.
3. En el PDF de solicitudes de consumo (`receipt.blade.php`), el encabezado corporativo imprime el nombre de la empresa incondicionalmente sin considerar si la empresa tiene `show_name` deshabilitado.

## What Changes

- Actualizar `HandleInertiaRequests.php` para compartir el valor real `(bool)(\App\Facades\CompanyFacade::getCompany()?->show_name ?? true)`.
- Modificar `AdminLayout.vue` para mostrar el nombre comercial de la empresa bajo el logo cuando `company.show_name` esté activo (y la barra no esté en modo mini/colapsada).
- Utilizar dinámicamente el logo cargado `company.logo_url` en lugar de una ruta estática en `AdminLayout.vue`.
- Actualizar `resources/views/admin/consumption-requests/receipt.blade.php` para condicionar la visualización del nombre corporativo según `$company->show_name`.

## Capabilities

### New Capabilities
<!-- None -->

### Modified Capabilities
- `company-branding-display`: Regula la visibilidad del nombre de la empresa en la barra lateral del menú administrativo y en las impresiones PDF según la configuración `show_name`.

## Impact

- **Frontend**: `resources/js/Layouts/AdminLayout.vue` (barra lateral y cabecera de marca).
- **Backend/Inertia**: `app/Http/Middleware/HandleInertiaRequests.php`.
- **Vistas Blade / PDF**: `resources/views/admin/consumption-requests/receipt.blade.php`.
