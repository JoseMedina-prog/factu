#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "$(grep '^APP_KEY=base64' .env)" ]; then
    php artisan key:generate --force
fi

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

php artisan migrate --force --no-interaction || echo "[entrypoint] migrate failed, will retry on first request"

exec "$@"