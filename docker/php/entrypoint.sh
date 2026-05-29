#!/bin/sh

set -e

# Pre-create necessary directories and fix permissions
# This ensures that even if volumes are mounted, we have the right structure
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         bootstrap/cache

# Note: In some environments, chmod/chown might fail if the user is not root.
# However, if directories already exist and are writable, this will proceed.
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Clean any host-generated caches that use absolute paths
rm -f bootstrap/cache/*.php
# Wait for database (PostgreSQL)
echo "Waiting for database..."
until php -r "try { new PDO('pgsql:host=db;port=5432;dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }"; do
  sleep 1
done
echo "Database is ready."

# Run migrations and seeders
php artisan migrate --force --seed

# Setup internal Docker symlink for uploaded images
rm -f public/storage
php artisan storage:link

# Optimize Laravel for production
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Execute the CMD from the Dockerfile
exec "$@"
