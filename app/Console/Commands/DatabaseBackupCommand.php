<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar backup local con fecha y hora exacta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de backup: Extrayendo Postgres a nivel local...');

        $database = env('DB_DATABASE', 'inventory_gclaure');
        $username = env('DB_USERNAME', 'postgres');
        $password = env('DB_PASSWORD', 'admin123');
        $host     = env('DB_HOST', 'db'); // Importante, host "db" que corresponde al docker container db

        $configuredPath = env('BACKUP_LOCAL_PATH', 'storage/app/backups');
        $backupDir = str_starts_with($configuredPath, '/') 
            ? $configuredPath 
            : base_path($configuredPath);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $date = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$date}.sql";
        $localPath = "{$backupDir}/{$filename}";

        $command = "PGPASSWORD='{$password}' pg_dump -h {$host} -U {$username} -F c -b -v -f '{$localPath}' {$database}";

        $this->info("Generando archivo local: {$filename}");
        $output = [];
        $returnVar = null;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->error("Error al generar el backup local. Código: {$returnVar}");
            return Command::FAILURE;
        }

        $this->info("¡Completado! Backup guardado en: {$localPath}");

        // Eliminar backups anteriores para no llenar el disco
        $this->info("Buscando backups anteriores para eliminar...");
        $files = File::files($backupDir);
        foreach ($files as $file) {
            if ($file->getExtension() === 'sql' && str_starts_with($file->getFilename(), 'backup_') && $file->getFilename() !== $filename) {
                $this->info("-> Eliminando backup anterior: " . $file->getFilename());
                File::delete($file->getPathname());
            }
        }

        return Command::SUCCESS;
    }
}
