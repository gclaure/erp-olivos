# Design

## Context

Para mantener el sistema minimalista y sin comandos adicionales en la consola, Laravel permite registrar tareas programadas basadas en clausuras (`Schedule::call(...)`) directamente en `routes/console.php`. Esto permite que el scheduler del sistema ejecute la rutina de mantenimiento en segundo plano sin generar archivos de comandos innecesarios en `app/Console/Commands/`.

## Goals / Non-Goals

**Goals:**
- Configurar un cron diario automático en `routes/console.php` mediante `Schedule::call(...)`.
- Ejecutar la limpieza de forma desatendida todos los días (ej. a las `03:00 AM`).
- Eliminar de la tabla `notifications` todos los registros (leídos y no leídos) con `created_at < now()->subDays(90)`.
- Registrar en log (`Log::info`) los registros eliminados.
- Proteger la tarea con `withoutOverlapping()` y `runInBackground()`.

**Non-Goals:**
- No crear comandos de consola (`Command`) independientes ni firmas CLI.
- No alterar las notificaciones creadas dentro de los últimos 90 días.

## Decisions

### 1. `Schedule::call()` en `routes/console.php`
- **Decisión:** Programar directamente en `routes/console.php`:
  ```php
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Log;
  use Illuminate\Support\Facades\Schedule;

  Schedule::call(function () {
      $cutoff = now()->subDays(90);
      $deleted = DB::table('notifications')
          ->where('created_at', '<', $cutoff)
          ->delete();

      if ($deleted > 0) {
          Log::info("Cron de notificaciones: Se eliminaron {$deleted} registros anteriores a 90 días ({$cutoff->toDateString()}).");
      }
  })->dailyAt('03:00')->name('limpiar-notificaciones-antiguas')->withoutOverlapping();
  ```
- **Razón:** Cero archivos adicionales, ejecución transparente y automática vía el cron de Laravel.

## Risks / Trade-offs

- **[Riesgo]** Bloqueo en transacciones de la tabla si existiesen millones de registros.
  - **Mitigación:** En PostgreSQL la eliminación por `created_at < cutoff` de forma diaria solo borra el lote generado en un solo día, lo que se ejecuta en milisegundos.
