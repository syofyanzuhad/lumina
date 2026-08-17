# Stage 1: Build frontend & assets
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build && npm run build:tracker

# Stage 2: Application runtime environment
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_mysql \
    bcmath \
    opcache \
    zip \
    intl \
    mbstring

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .
COPY --from=assets /app/public/build ./public/build
COPY --from=assets /app/public/js/script.js ./public/js/script.js

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY deployment/docker/nginx.conf /etc/nginx/http.d/default.conf
COPY deployment/supervisor/lumina-worker.conf /etc/supervisor/conf.d/lumina-worker.conf

COPY deployment/docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
