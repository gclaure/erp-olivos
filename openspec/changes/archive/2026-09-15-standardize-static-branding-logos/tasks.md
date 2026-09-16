## 1. Limpieza y Actualización de la Vista Company.vue

- [x] 1.1 Remover imports de Dropzone, sus hojas de estilo y propiedades reactivas (`dropzoneRef`, `dropzoneInstance`, `form.logo`) en `resources/js/Pages/Admin/Settings/Company.vue`
- [x] 1.2 Eliminar el contenedor HTML del Dropzone y los mensajes asociados en el formulario de `Company.vue`
- [x] 1.3 Eliminar los estilos CSS scoped relacionados a `.dropzone` en `Company.vue`
- [x] 1.4 Actualizar la tarjeta de previsualización para utilizar estáticamente `/img/logo-light.png`

## 2. Estandarización de Logos en Layouts y Comprobantes

- [x] 2.1 Verificar que `resources/js/Layouts/AdminLayout.vue` utilice `/img/logo-dark.png` junto con el condicional de `company?.show_name`
- [x] 2.2 Actualizar `resources/views/admin/consumption-requests/receipt.blade.php` para apuntar a `public_path('img/logo-light.png')` de forma fija y validar el renderizado del nombre

## 3. Limpieza de Backend y Validación

- [x] 3.1 Remover la regla de validación de `logo` en `app/Http/Requests/Admin/UpdateCompanyRequest.php` y simplificar `CompanyService.php` si aplica
- [x] 3.2 Compilar assets con `npm run build` y verificar que no existan errores ni advertencias
