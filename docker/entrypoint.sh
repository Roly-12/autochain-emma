#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

php artisan config:clear
php artisan migrate --force
php artisan autochain:bootstrap-admin
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache

# Best effort sur Render gratuit : suspendu lorsque le service est en veille.
php artisan schedule:work --no-interaction >> /proc/1/fd/1 2>&1 &

exec "$@"
