# ---- Frontend build stage ----
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm install && npm run build


# ---- PHP application stage ----
FROM php:8.3-fpm-bookworm AS app

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libonig-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        zip \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip gd exif opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && composer clear-cache

COPY --from=frontend /app/public/build ./public/build

# Snapshot the baked public dir so the entrypoint can seed the shared nginx volume
RUN cp -a public public-seed

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN mkdir -p storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             storage/app/public \
    && chown -R www-data:www-data storage bootstrap/cache public public-seed

USER www-data

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
