FROM dunglas/frankenphp:1-php8.5-bookworm

# Install system dependencies
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    opcache

# Install Composer
COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

WORKDIR /app
ENV PORT=10000

# Copy Laravel application
COPY . .

# Reduce simultaneous Composer downloads
ENV COMPOSER_MAX_PARALLEL_HTTP=1 \
    COMPOSER_PROCESS_TIMEOUT=2000

# Install production dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Enable PHP production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set Laravel writable directories
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]