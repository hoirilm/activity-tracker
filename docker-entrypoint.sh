#!/bin/sh
set -e

# Ensure all storage and cache directories exist
mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
touch storage/logs/laravel.log

# Run migrations automatically only if explicitly requested
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration skipped or failed"
fi

# Optimization caching for production
echo "Caching Laravel configuration & routes..."
php artisan config:cache || echo "Config cache skipped"
php artisan route:cache || echo "Route cache skipped"
php artisan view:cache || echo "View cache skipped"

# Dynamically add Nginx port based on $PORT variable (default: 8080)
export PORT=${PORT:-8080}
echo "Assigned PORT: $PORT"
sed -i "s/server {/server {\n    listen ${PORT};/g" /etc/nginx/http.d/default.conf

echo "Fixing storage permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start supervisor
echo "Starting Nginx & PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
