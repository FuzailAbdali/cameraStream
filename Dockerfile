FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y git unzip libzip-dev ffmpeg \
    && docker-php-ext-install pdo pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist \
    && cp .env.example .env \
    && php artisan key:generate

CMD ["sh", "-c", "php artisan migrate --force && php artisan queue:work --tries=1 --timeout=0 & php artisan serve --host=0.0.0.0 --port=8000"]
