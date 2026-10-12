#!/bin/sh
# Demo container start-up: build a fresh demo database, cache config, then serve.
set -e

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY is not set" >&2
  exit 1
fi

# Render generates a plain random secret; Laravel needs a "base64:" 32-byte key, so derive one from it.
case "$APP_KEY" in
  base64:*) ;;
  *) APP_KEY=$(php -r 'echo "base64:".base64_encode(hash("sha256", getenv("APP_KEY"), true));'); export APP_KEY ;;
esac

touch database/database.sqlite
php artisan migrate:fresh --seed --force
php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Nightly demo reset while the container stays awake.
php artisan schedule:work >/dev/null 2>&1 &

exec frankenphp php-server --root public --listen ":${PORT:-8080}"
