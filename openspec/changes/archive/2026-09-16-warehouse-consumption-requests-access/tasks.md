## 1. Migración de Permisos en Base de Datos

- [x] 1.1 Crear migración de Laravel para asignar el permiso `manage-consumption` al rol `Almacén` de forma idempotente
- [x] 1.2 Ejecutar la migración con `php artisan migrate` y resetear la caché de permisos de Spatie

## 2. Verificación de Menú Lateral y Rutas

- [x] 2.1 Verificar que `SidebarService.php` entregue el ítem "Consumos Solicitados" para usuarios con rol `Almacén`
- [x] 2.2 Comprobar que "Registrar Consumo" continúe oculto y bloqueado para Almacén

## 3. Pruebas Operativas de Acceso y Navegación

- [x] 3.1 Probar la respuesta y listado en `/admin/consumption-requests` bajo el usuario con rol Almacén (`almacen@gmail.com`)
- [x] 3.2 Validar que los roles Consumidor y Administrador conserven intactos sus flujos y pantallas sin regresiones
