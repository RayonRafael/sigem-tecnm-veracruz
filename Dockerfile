FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev libicu-dev \
    && docker-php-ext-install pdo_sqlite zip intl \
    && rm -rf /var/lib/apt/lists/*

# BATCH 3A: Habilitar compresión zlib para respuestas PHP/HTTP en Render
RUN echo "zlib.output_compression = On" > /usr/local/etc/php/conf.d/compression.ini \
    && echo "zlib.output_compression_level = 6" >> /usr/local/etc/php/conf.d/compression.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# APP_KEY se proporciona via variable de entorno en Render Dashboard
# NUNCA hardcodear la APP_KEY en este archivo
ENV APP_ENV=production \
    APP_DEBUG=false \
    DB_CONNECTION=sqlite \
    DB_DATABASE=database/database.sqlite

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader \
    && php artisan filament:assets \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 10000

CMD php artisan serve --host=0.0.0.0 --port=$PORT

