# syntax=docker/dockerfile:1

# ---- base: PHP-FPM runtime shared by dev and prod ---------------------------
FROM php:8.4-fpm-alpine AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_pgsql pdo_sqlite intl bcmath zip opcache pcntl

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY --chmod=0755 docker/php/entrypoint.sh /usr/local/bin/entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ---- vendor: production dependencies (cached on composer.json/lock) ---------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# ---- dev: full toolchain (pcov, composer, dev dependencies) -----------------
FROM base AS dev

# DEV ONLY: runs as root (php-fpm -R) because bind mounts from Windows/macOS hosts are root-owned,
# which would make storage/ and the sources unwritable for www-data. The prod target is non-root.
RUN install-php-extensions pcov
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/fpm-dev.conf /usr/local/etc/php-fpm.d/zz-dev.conf

COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-interaction \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

ENV APP_ENV=local
CMD ["php-fpm", "-R"]

# ---- prod: no dev dependencies, optimized autoload, production opcache ------
FROM base AS prod

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts --no-interaction \
    && rm -f /usr/local/bin/composer \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/conf.d/opcache-prod.ini /usr/local/etc/php/conf.d/zz-opcache-prod.ini

ENV APP_ENV=production \
    APP_DEBUG=false
USER www-data
