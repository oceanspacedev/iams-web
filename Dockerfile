# ============================================================
# STAGE 1: Build Frontend Assets (Vite + Vue 3 + Tailwind)
# ============================================================
FROM node:20-alpine AS frontend
WORKDIR /app

# Copy dependency definitions
COPY package*.json ./
RUN npm ci

# Copy configuration and source files
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY public ./public

# Build production assets to /app/public/build
RUN npm run build

# ============================================================
# STAGE 2: PHP 8.2 FPM Runtime (Production Ready)
# ============================================================
FROM php:8.2-fpm-alpine AS app

# Install system dependencies
RUN apk add --no-cache \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    shadow \
    bash

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache \
        intl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Copy built frontend assets from STAGE 1
COPY --from=frontend /app/public/build /var/www/public/build

# Install PHP dependencies for production
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy custom PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Setup entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set correct ownership for web user
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
