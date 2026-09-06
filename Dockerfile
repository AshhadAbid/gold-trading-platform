FROM node:22-alpine AS frontend
WORKDIR /frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
ARG VITE_API_URL=/api/v1
ENV VITE_API_URL=$VITE_API_URL
RUN npm run build

FROM php:8.4-cli-alpine
RUN apk add --no-cache git unzip libpq-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql \
    && apk del $PHPIZE_DEPS
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader
COPY backend/ ./
COPY --from=frontend /frontend/dist ./public
RUN mv public/index.html public/app.html \
    && composer run-script post-autoload-dump \
    && chmod -R 775 storage bootstrap/cache
EXPOSE 10000
CMD ["sh", "-c", "export APP_KEY=base64:${RENDER_APP_KEY}; php artisan migrate --seed --force; php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
