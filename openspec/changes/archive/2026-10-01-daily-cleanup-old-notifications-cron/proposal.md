# Proposal

## Why

La tabla `notifications` acumula registros de forma continua con las operaciones del sistema. Sin un mecanismo automático, el volumen de registros satura el almacenamiento y ralentiza las consultas de la aplicación. Se requiere una tarea programada interna (cron/schedule) en Laravel que corra de forma 100% desatendida y automática todos los días directamente desde `routes/console.php`, manteniendo una ventana móvil de los últimos 90 días y eliminando todas las notificaciones (leídas y no leídas) anteriores a esa fecha, sin crear comandos Artisan de consola adicionales.

## What Changes

- **Programación de clausura diaria en `routes/console.php`:** Configurar un `Schedule::call(...)` que se ejecute a diario (ej. a las `03:00` o medianoche) de manera automática.
- **Eliminación directa por Eloquent/Query Builder:** Eliminar directamente los registros en la tabla `notifications` donde `created_at < now()->subDays(90)`.
- **Trazabilidad en Log:** Registrar en `storage/logs/laravel.log` (`Log::info`) la cantidad de notificaciones purgadas en cada ejecución del cron.

## Capabilities

### Modified Capabilities
- `consumption-request-notifications`: Incorporar la rutina de cron diaria automática para purga de notificaciones que superen los 90 días de antigüedad.

## Impact

- **Backend:** Exclusivamente `routes/console.php`.
- **Base de datos:** Purgado automático diario en la tabla `notifications` en PostgreSQL.
- **Frontend / CLI:** Sin nuevos comandos CLI ni cambios en interfaces de usuario.
