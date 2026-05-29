<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;
use Exception;

class CreateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea la base de datos definida en .env si no existe';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $database = Config::get('database.connections.pgsql.database');
        
        if (!$database) {
            $this->error('No se pudo encontrar el nombre de la base de datos en la configuración.');
            return self::FAILURE;
        }

        try {
            // Intentamos conectar a la base de datos 'postgres' para verificar existencia y crear
            $config = Config::get('database.connections.pgsql');
            $config['database'] = 'postgres';
            
            Config::set('database.connections.temp_pgsql', $config);
            
            $query = "SELECT 1 FROM pg_database WHERE datname = '{$database}'";
            $exists = DB::connection('temp_pgsql')->selectOne($query);

            if ($exists) {
                $this->info("La base de datos '{$database}' ya existe.");
                return self::SUCCESS;
            }

            $this->info("Creando la base de datos '{$database}'...");
            
            // PostgreSQL no permite CREATE DATABASE dentro de una transacción o con sentencias preparadas fácilmente para el nombre
            // Usamos una conexión PDO directa para evitar problemas de abstracción de Laravel en este paso específico
            $host = $config['host'];
            $port = $config['port'];
            $user = $config['username'];
            $pass = $config['password'];
            
            $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE \"{$database}\" ENCODING 'UTF8'");

            $this->info("La base de datos '{$database}' ha sido creada exitosamente.");
            
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error("Error al crear la base de datos: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
