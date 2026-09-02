## 1. Sidebar Menu Reorganization

- [x] 1.1 Extraer las opciones de consumo (`admin.consumption-requests.create` y `admin.consumption-requests.index`) del grupo "Inventario" en `app/Services/SidebarService.php`
- [x] 1.2 Definir el nuevo grupo desplegable "Consumos" en `app/Services/SidebarService.php` conteniendo ambas rutas
- [x] 1.3 Asegurar que la lógica de permisos en `filterByPermissions()` soporte la visibilidad correcta del grupo para roles Consumidor, Almacén y Administrador

## 2. Verification and Visual Consistency

- [x] 2.1 Verificar que el sidebar renderice correctamente el desplegable "Consumos" en `resources/js/Layouts/AdminLayout.vue`
- [x] 2.2 Validar que el título y breadcrumbs en `resources/js/Layouts/Partials/AdminNavbar.vue` muestren adecuadamente la etiqueta activa
- [x] 2.3 Probar la navegación con diferentes roles de usuario (Admin, Consumidor, Almacén)
