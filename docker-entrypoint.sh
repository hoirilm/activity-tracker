#!/bin/sh
set -e

# Fix permissions
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache
chmod -R 777 storage bootstrap/cache

# Run migrations automatically only if explicitly requested
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || echo "Migration skipped or failed"
    # Seeders should only be run on fresh setups. Commented out to prevent data duplication on shared DB.
    # php artisan db:seed --force || echo "Seeder skipped"
fi

# Optimization caching for production
echo "Caching Laravel configuration & routes..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Dynamically update Nginx port based on Railway's $PORT variable (fallback to 8080)
export PORT=${PORT:-8080}
echo "Railway assigned PORT: $PORT"
sed -i "s/listen 8080;/listen ${PORT};/g" /etc/nginx/http.d/default.conf

echo "--- NGINX CONFIG ---"
cat /etc/nginx/http.d/default.conf
echo "--------------------"

# Start supervisor
echo "Starting Nginx & PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
