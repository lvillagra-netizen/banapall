#!/bin/sh
set -e

cd /var/www/html

if [ "${DB_CONNECTION:-pgsql}" = "pgsql" ]; then
    echo "Esperando PostgreSQL en ${DB_HOST:-db}:${DB_PORT:-5432}..."
    until pg_isready -h "${DB_HOST:-db}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-banapal}" >/dev/null 2>&1; do
        sleep 1
    done
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

php artisan package:discover --ansi || true
php artisan storage:link >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --force || true
fi

if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

exec php-fpm
