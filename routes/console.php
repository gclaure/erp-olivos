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
