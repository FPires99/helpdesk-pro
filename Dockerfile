# --- Front-end assets ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY app/Enums ./app/Enums
RUN npm run build

# --- PHP application (FrankenPHP web server) ---
FROM dunglas/frankenphp:1-php8.3-bookworm

RUN install-php-extensions pdo_sqlite gd zip intl opcache pcntl

# The image gives the frankenphp binary the "bind to low ports" capability. Platforms such as Render
# refuse to run binaries with extra capabilities ("Operation not permitted"), and we listen on 8080
# anyway, so remove it.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcap2-bin \
    && (setcap -r /usr/local/bin/frankenphp || true) \
    && apt-get purge -y libcap2-bin && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev \
    && chmod +x docker/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache database

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/database/database.sqlite \
    SESSION_DRIVER=database \
    CACHE_STORE=database \
    QUEUE_CONNECTION=sync \
    MAIL_MAILER=log \
    HELPDESK_DEMO=true

EXPOSE 8080
CMD ["docker/start.sh"]
