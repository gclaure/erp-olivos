# Tasks

## 1. Base de Datos y Registro del Rol Bodas

- [x] 1.1 Crear migración idempotente para registrar el rol `Bodas` con `guard_name = 'web'`, sincronizar el permiso `manage-orders` y ejecutar `php artisan migrate`.
- [x] 1.2 Actualizar `RolesAndPermissionsSeeder.php` para incluir el rol `Bodas` con permiso predeterminado `manage-orders`.

## 2. Protección de Rutas y Menú de Navegación

- [x] 2.1 Proteger el grupo de rutas `orders` en `routes/admin.php` con el middleware `permission:manage-orders`.
- [x] 2.2 Ajustar `SidebarService.php` para que el rol `Bodas` tenga acceso al Dashboard y al grupo de Pedidos, manteniendo el resto de módulos restringidos.

## 3. Backend del Dashboard de Pedidos (Bodas)

- [x] 3.1 Actualizar `DashboardController.php` para detectar usuarios con rol `Bodas` (`$isBodas`), calculando métricas operativas (pedidos activos, entregas de hoy, entregas de los próximos 7 días, cancelados y listado de entregas urgentes) y renderizando `Admin/BodasDashboard`.

## 4. Frontend del Dashboard de Bodas y Verificación

- [x] 4.1 Crear el componente Vue 3 `resources/js/Pages/Admin/BodasDashboard.vue` con diseño responsive, tokens de modo oscuro, tarjetas de métricas, accesos rápidos y tabla de entregas programadas.
- [x] 4.2 Compilar los assets del frontend con `npm run build` y verificar que la compilación termine sin errores.
