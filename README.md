# Inventario GClaure - Sistema de Inventarios Pro

Sistema de gestión de inventarios profesional construido con **Laravel 12** y **Livewire 3**.

## 🚀 Requisitos Previos

- Docker y Docker Compose instalados.

## 🛠️ Stack Tecnológico

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **UI Components**: Wire UI
- **Base de Datos**: PostgreSQL 16
- **Cache & Performance**: OPcache habilitado
- **Tunel**: Ngrok integrado para dominio público fijo

## 📦 Instalación y Despliegue (Production Ready)

### 1. Levantar de cero (Primera vez o para aplicar cambios profundos)

Si es la primera vez que clonas el proyecto o hiciste cambios importantes en los `Dockerfile` o necesitas reiniciar todo desde cero, usa este comando. 

```bash
docker compose down && docker compose up -d --build
```

```crear base de datos
php artisan db:create
```

### ¿Qué hace este comando al levantar de cero?
1.  **Construye** la imagen de la aplicación con PHP 8.4-FPM.
2.  **Levanta** los servicios: `app` (PHP-FPM), `web` (Nginx), `db` (PostgreSQL) y `ngrok` (Túnel).
3.  **Limpia** cualquier caché generada en tu Mac que pueda generar conflictos de rutas.
4.  **Ejecuta** automáticamente las migraciones y los seeders (`php artisan migrate --force --seed`).
5.  **Crea** el enlace simbólico para imágenes (`public/storage`) apuntando correctamente dentro de Docker.
6.  **Optimiza** el rendimiento de Laravel (`optimize:clear`, `config:cache`, `route:cache`, `view:cache`).
7.  **Configura** los permisos necesarios `777` para `storage` y `bootstrap/cache`.

### 2. Solo levantar los servicios (Día a día)

Si ya levantaste el proyecto antes y apagaste tu computadora, solo necesitas encender los servicios rápidamente:

```bash
docker compose up -d
```

## 🌐 Acceso al Sistema

- **URL Local**: [http://localhost:8080](http://localhost:8080)
- **URL Pública**: La URL configurada en la variable `APP_URL` (vía Ngrok).
- **Base de Datos**: `localhost:5432`
## 🐳 Configuración Docker

El proyecto utiliza una arquitectura desacoplada:

- `docker/php/Dockerfile`: Configuración de PHP 8.4-FPM optimizada.
- `docker/nginx/default.conf`: Configuración de Nginx con Gzip y seguridad.
- `docker/php/entrypoint.sh`: Script de automatización de arranque.

## 🔑 Credenciales de la Base de Datos (Default)

- **Database**: `inventory_gclaure`
- **User**: `postgres`
- **Password**: `admin123`

## 🛠 Comandos Útiles

**Bajar todos los servicios:**
```bash
docker compose down
```

**Reiniciar la aplicación (PHP-FPM):**
```bash
docker compose restart app
```

**Ver logs en tiempo real:**
```bash
docker compose logs -f
```

**Acceder a la terminal del contenedor:**
```bash
docker exec -it inventory-app bash
```

**Limpiar caché de rutas (conflictos Docker vs Entorno Local):**
Si obtienes el error `There is no existing directory at "/var/www/storage/logs"...` al cambiar entre la ejecución de Docker y la máquina local, necesitas limpiar la configuración persistida en el caché:
```bash
php artisan config:clear
php artisan storage:link
php artisan route:clear
php artisan view:clear
php artisan config:clear
```
---
### Actualizar Código sin Perder Datos

Si has modificado el código fuente (controladores, vistas, etc.) o necesitas correr nuevas migraciones **sin afectar tus datos y sin ejecutar seeders**, utiliza los siguientes comandos con los contenedores encendidos:

**1. Limpiar Caché y Refrescar Código:**
```bash
docker compose exec app php artisan optimize:clear
```

**2. Ejecutar Migraciones Nuevas:**
```bash
docker compose exec app php artisan migrate --force
```

**3. Compilar Assets (Opcional, si cambiaste JS/CSS):**
```bash
docker compose exec app pnpm run build
```
---
Sistema desarrollado siguiendo las reglas de negocio y arquitectura especificadas para Inventory GClaure.

### Backup de la base de datos
```bash
docker compose exec app php artisan db:backup
```

### para aplicar cambios a docker sin borrar datos 
```bash
docker compose up -d --build
```
### comando para levantar socket
```bash
php artisan reverb:start
```

### comando para reiniciar base de datos con todos los datos
```bash
php artisan migrate:fresh --seed
```