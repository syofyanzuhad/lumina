#!/bin/sh
set -e

if [ "$APP_ENV" = "production" ]; then
    echo "Running production optimizations..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "Running database migrations..."
php artisan migrate --force

echo "Starting application services via Supervisor..."
exec supervisord -c /etc/supervisor/supervisord.conf
