#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Only the php-fpm service prepares the application; one-off commands pass through.
if [ "${1:-}" = "php-fpm" ]; then
    if ! grep -Eq '^APP_KEY=.+' .env && [ -z "${APP_KEY:-}" ]; then
        php artisan key:generate --force
    fi

    attempts=0
    until php artisan db:show >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            echo "Database not reachable after 30 attempts." >&2
            exit 1
        fi
        sleep 2
    done

    php artisan migrate --force

    if [ "${APP_SEED:-false}" = "true" ]; then
        php artisan db:seed --force
    fi
fi

exec "$@"
