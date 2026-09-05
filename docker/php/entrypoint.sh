#!/bin/sh
set -e

mkdir -p storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         storage/app/public \
         storage/app/reports

# Seed the shared public volume (served by nginx) with baked assets
cp -a /var/www/html/public-seed/. /var/www/html/public/

php artisan storage:link || true

exec "$@"
