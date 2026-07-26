# ============================================
# Stage 1: Build Frontend Assets (Vite)
# ============================================
FROM node:20-alpine AS build-frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# ============================================
# Stage 2: Install PHP Composer Dependencies
# ============================================
FROM composer:2 AS build-backend
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ============================================
# Stage 3: Production Runtime (PHP 8.3 + Nginx)
# ============================================
FROM php:8.3-fpm-alpine AS production

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        gd \
        zip \
        intl \
        mbstring \
        xml \
        bcmath \
        opcache

# Configure OPCache for production performance
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .
COPY --from=build-backend /app/vendor ./vendor
COPY --from=build-frontend /app/public/build ./public/build

# Copy Docker configurations
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Set directory permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 8080
EXPOSE 8080

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
