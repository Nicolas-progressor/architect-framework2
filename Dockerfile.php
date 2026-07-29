FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mysqli mbstring
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Expose port
EXPOSE 9000

# Create directories with proper permissions
RUN mkdir -p /var/www/html/storage /var/www/html/cache /var/www/html/storage/blueprint /var/www/html/storage/cache /var/www/html/storage/logs && \
    chown -R www-data:www-data /var/www/html

RUN echo '#!/bin/bash\n\
mkdir -p /var/www/html/storage /var/www/html/cache /var/www/html/storage/blueprint /var/www/html/storage/cache /var/www/html/storage/logs\n\
find /var/www/html -path /var/www/html/.git -prune -o -path /var/www/html/vendor -prune -o -type d -exec chmod 755 {} +\n\
find /var/www/html -path /var/www/html/.git -prune -o -path /var/www/html/vendor -prune -o -type f -exec chmod 644 {} +\n\
sed -i "s/^user = www-data/user = root/" /usr/local/etc/php-fpm.d/www.conf\n\
sed -i "s/^group = www-data/group = root/" /usr/local/etc/php-fpm.d/www.conf\n\
exec docker-php-entrypoint php-fpm --allow-to-run-as-root\n' > /entrypoint.sh && chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
