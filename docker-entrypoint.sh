#!/bin/sh
set -e

# Fix permissions
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chmod -R 777 storage bootstrap/cache

# Run migrations automatically if database is accessible
if [ "$RUN_MIGRATIONS" = "true" ] || [ -n "$DB_HOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration skipped or failed"
    php artisan db:seed --force || echo "Seeder skipped"
fi

# Optimization caching for production
echo "Caching Laravel configuration & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start supervisor
echo "Starting Nginx & PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
