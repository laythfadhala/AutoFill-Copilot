FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libfontconfig1 \
    fontconfig \
    zip \
    unzip \
    nginx \
    poppler-utils \
    imagemagick \
    tesseract-ocr \
    tesseract-ocr-deu \
    tesseract-ocr-ara \
    redis-tools

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Create fontconfig cache directory with proper permissions
RUN mkdir -p /var/cache/fontconfig && chmod 755 /var/cache/fontconfig
RUN mkdir -p /usr/local/share/fonts && chmod 755 /usr/local/share/fonts

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

# Copy custom PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy release reserved script
COPY docker/release_reserved.sh /scripts/release_reserved.sh
RUN chmod +x /scripts/release_reserved.sh

# Create Laravel storage and bootstrap cache directories
RUN mkdir -p storage/logs storage/framework/sessions storage/framework/views storage/framework/cache
RUN chmod -R 775 storage bootstrap/cache

# Create fontconfig cache directory in app directory and make it writable
RUN mkdir -p /var/www/html/storage/fontconfig-cache && chmod 777 /var/www/html/storage/fontconfig-cache

# Initialize fontconfig cache
RUN fc-cache -f

# Set fontconfig cache directory
ENV FONTCONFIG_CACHE=/var/www/html/storage/fontconfig-cache

# Expose port 8000
EXPOSE 8000

# Start services
CMD php-fpm -D && nginx -g 'daemon off;'
