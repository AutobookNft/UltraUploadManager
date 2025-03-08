FROM php:8.3-fpm

# Installa dipendenze di sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Installa estensioni PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath

# Installa Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Imposta la working directory
WORKDIR /var/www/html

# Copia tutti i file e le directory
COPY . /var/www/html

# Copia esplicitamente il file .env (file nascosto)
COPY .env /var/www/html/.env

# Installa le dipendenze PHP
RUN composer install --no-scripts --no-interaction

# Configura permessi
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chown www-data:www-data /var/www/html/.env
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod 664 /var/www/html/.env

# Espone la porta per Laravel
EXPOSE 8000

# Comando di default
CMD php artisan serve --host=0.0.0.0 --port=8000
