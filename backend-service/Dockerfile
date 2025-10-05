FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Create Laravel storage and bootstrap cache directories
RUN mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache
RUN chmod -R 775 storage bootstrap/cache

# Expose port 8000
EXPOSE 8000

# Start services
CMD php-fpm -D && nginx -g 'daemon off;'
