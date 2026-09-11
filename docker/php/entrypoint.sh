#!/bin/bash
set -e

mkdir -p public/build && cp -r /opt/build/. public/build/

if [ "$1" = "php-fpm" ]; then
    if ! grep -qE '^APP_KEY=.+' .env; then
        php artisan key:generate --force
    fi
    until php artisan migrate --force --seed >/dev/null 2>&1; do
        echo "waiting for database..."
        sleep 3
    done
    php artisan config:cache
    php artisan route:cache
else
    until grep -qE '^APP_KEY=.+' .env; do
        echo "waiting for application key..."
        sleep 2
    done
fi

exec "$@"
