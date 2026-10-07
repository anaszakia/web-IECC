#!/bin/sh
set -e

echo "Optimizing Laravel..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Starting Supervisord (Nginx, PHP-FPM, Queue Worker, Reverb)..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
