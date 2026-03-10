FROM php:8.4-apache

# 1. Install sistem dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    unzip \
    git \
    curl \
    nano \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# 3. Enable Apache mod_rewrite
RUN a2enmod rewrite

# 4. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Set working directory
WORKDIR /var/www/html

# 6. Set environment variable for build context
ENV DOCKER_ENV=true
ENV APP_ENV=local
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 7. Copy composer files DULU (supaya cache layer efisien saat composer tidak berubah)
COPY composer.json composer.lock ./

# 8. Install dependencies di DALAM IMAGE (bukan mount dari Windows - ini penyebab hang!)
RUN composer install --no-scripts --no-autoloader --ignore-platform-reqs

# 9. Copy seluruh kode aplikasi
COPY . .

# 10. Pastikan .env ada saat build (artisan butuh ini)
RUN if [ ! -f .env ]; then cp .env.example .env && php artisan key:generate --ansi; fi

# 11. Generate autoloader dan jalankan package discovery
RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi 2>/dev/null || true

# 12. Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
