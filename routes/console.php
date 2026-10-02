<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (env('BACKUP_ENABLED', true)) {
    $backupTime = env('BACKUP_SCHEDULE_TIME', '23:00');
    \Illuminate\Support\Facades\Schedule::command('db:backup')->dailyAt($backupTime);
}

/**
 * Cron diario: Limpieza automática de notificaciones antiguas.
 * Mantiene una ventana móvil de los últimos 90 días eliminando
 * tanto las leídas como las no leídas anteriores a ese período.
 */
\Illuminate\Support\Facades\Schedule::call(function () {
    $cutoff = now()->subDays(90);
    $deleted = \Illuminate\Support\Facades\DB::table('notifications')
        ->where('created_at', '<', $cutoff)
        ->delete();

    if ($deleted > 0) {
        \Illuminate\Support\Facades\Log::info("Cron notificaciones: Se eliminaron {$deleted} notificaciones anteriores a 90 días ({$cutoff->toDateString()}).");
    }
})->dailyAt('03:00')
  ->name('limpiar-notificaciones-antiguas-90-dias')
  ->withoutOverlapping();

