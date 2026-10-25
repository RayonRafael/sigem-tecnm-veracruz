FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev libicu-dev \
    && docker-php-ext-install pdo_sqlite zip intl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:kX/Ss1wi677iwLQrFpWXOfSgaLk4VCI7sV4/PIbmFmc= \
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
