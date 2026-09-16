## 1. Backend & Middleware

- [x] 1.1 Actualizar `HandleInertiaRequests.php` para compartir el valor real de `show_name` desde `CompanyFacade::getCompany()?->show_name`.
- [x] 1.2 Actualizar `receipt.blade.php` para condicionar el encabezado del nombre de la empresa con `show_name`.

## 2. Frontend & Sidebar

- [x] 2.1 Actualizar `AdminLayout.vue` para mostrar el nombre de la empresa bajo el logo cuando `company.show_name` esté activo y la barra no esté colapsada.
- [x] 2.2 Verificar compilación con `npm run build` y comprobar la reactividad en el menú y PDF.
