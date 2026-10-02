# Tasks

## 1. Programación Automática Diaria (Scheduler)

- [x] 1.1 Configurar en `routes/console.php` la tarea `Schedule::call(...)` que se ejecute a diario a las `03:00` eliminando notificaciones con más de 90 días y registrando en log
- [x] 1.2 Verificar la sintaxis y registro en el scheduler mediante `php artisan schedule:list` para comprobar que la tarea automática está activa y calendarizada diariamente
