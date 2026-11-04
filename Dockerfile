FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libsqlite3-dev libicu-dev \
    && docker-php-ext-install pdo_sqlite zip intl \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

# BATCH 3A: Habilitar compresión zlib para respuestas PHP/HTTP en Render
RUN echo "zlib.output_compression = On" > /usr/local/etc/php/conf.d/compression.ini \
    && echo "zlib.output_compression_level = 6" >> /usr/local/etc/php/conf.d/compression.ini

# Configurar DocumentRoot de Apache a la carpeta public de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

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
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader \
    && php artisan filament:assets \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 10000

# Ajustar puerto dinámico de Render ($PORT) y arrancar Apache en primer plano
CMD sh -c "sed -i 's/80/'\"\${PORT:-10000}\"'/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && exec apache2-foreground"
