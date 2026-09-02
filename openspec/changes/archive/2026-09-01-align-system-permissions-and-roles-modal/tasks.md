## 1. Database Permissions Migration & Sync

- [x] 1.1 Crear migración para actualizar registros en la tabla `permissions`: renombrar `create-sales` a `create-consumption`, agregar `manage-consumption` y limpiar permisos obsoletos
- [x] 1.2 Ejecutar migración y sincronizar permisos por defecto para roles `Consumidor`, `Almacén` y `Administrador`
- [x] 1.3 Actualizar seeders (`RolesAndPermissionsSeeder.php` y `InitialSetupSeeder.php`) con la nueva lista de permisos

## 2. Backend Controllers & Resources

- [x] 2.1 Actualizar `$permissionTranslations` en `app/Http/Controllers/SuperAdmin/RoleController.php`
- [x] 2.2 Actualizar traducciones de permisos en `app/Http/Resources/SuperAdmin/RoleResource.php`
- [x] 2.3 Actualizar `app/Services/SidebarService.php` para asignar los permisos `create-consumption` y `manage-consumption` a las rutas correspondientes

## 3. Frontend Views & Modals

- [x] 3.1 Actualizar `permissionDescriptions` y traducciones en `resources/js/Pages/SuperAdmin/Roles/Index.vue`
- [x] 3.2 Actualizar `permissionDescriptions` en `resources/js/Pages/SuperAdmin/Tenants/Partials/AddRoleModal.vue`

## 4. Verification

- [x] 4.1 Abrir el modal "Editar Rol" y comprobar que aparezca "Registrar Consumo" (`create-consumption`) y la lista limpia de permisos con sus descripciones
- [x] 4.2 Probar asignación y desasignación de permisos en un rol personalizado
- [x] 4.3 Validar acceso al sidebar con roles Consumidor, Almacén y Administrador
