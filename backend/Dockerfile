FROM php:8.4-cli-alpine
RUN apk add --no-cache git unzip libpq-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql \
    && apk del $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --no-dev
EXPOSE 8000
CMD ["sh", "-c", "php artisan migrate --seed --force && php artisan serve --host=0.0.0.0 --port=8000"]
