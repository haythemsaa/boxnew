#==============================================================================
# BoxiBox Production Dockerfile
#==============================================================================
# Multi-stage build for optimized production image
#==============================================================================

#------------------------------------------------------------------------------
# Stage 1: Composer Dependencies
#------------------------------------------------------------------------------
FROM composer:2 AS composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Copy application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

#------------------------------------------------------------------------------
# Stage 2: Node.js Assets Build
#------------------------------------------------------------------------------
FROM node:20-alpine AS node

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci

# Copy source files needed for build
COPY resources ./resources
COPY vite.config.js tailwind.config.js postcss.config.js ./

# Build assets
RUN npm run build

#------------------------------------------------------------------------------
# Stage 3: Production Image
#------------------------------------------------------------------------------
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    mysql-client \
    redis \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Create custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www/html

# Create non-root user
RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www

# Copy application from composer stage
COPY --from=composer --chown=www:www /app .

# Copy built assets from node stage
COPY --from=node --chown=www:www /app/public/build ./public/build

# Create required directories
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Set permissions
RUN chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Create storage link
RUN php artisan storage:link || true

# Optimize application
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Switch to non-root user
USER www

# Expose PHP-FPM port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php artisan health:check --json || exit 1

# Start PHP-FPM
CMD ["php-fpm"]
