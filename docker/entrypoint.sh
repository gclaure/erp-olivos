#!/bin/sh
set -e

# Esperar a que la base de datos esté lista si es necesario (opcional)
# echo "Esperando a la base de datos..."

# Ejecutar migraciones (Seguro de ejecutar en cada deploy)
php artisan migrate --force

# Enlace simbólico de almacenamiento
php artisan storage:link --force

# Optimización de caché para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Ejecutar el comando principal (Supervisor)
exec "$@"
