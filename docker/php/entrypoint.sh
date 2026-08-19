#!/bin/sh
set -e

mkdir -p storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         storage/app/public

php artisan storage:link || true

exec "$@"
