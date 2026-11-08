#!/bin/sh
set -e

cd /var/www/html

# Si se define DB_HOST, espera brevemente (máximo 10 segundos) en lugar de colgarse indefinidamente
if [ -n "$DB_HOST" ]; then
    echo "Comprobando conexión a PostgreSQL en ${DB_HOST}:${DB_PORT:-5432}..."
    COUNT=0
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" >/dev/null 2>&1 || [ $COUNT -ge 5 ]; do
        sleep 2
        COUNT=$((COUNT + 1))
    done
fi

# Permisos de carpetas
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

php artisan package:discover --ansi || true
php artisan storage:link >/dev/null 2>&1 || true

# Ejecutar migraciones si está habilitado
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force || true
fi

# Limpieza y preparación de caché
php artisan config:clear || true

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Iniciar servidor web en el puerto asignado por Render
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"