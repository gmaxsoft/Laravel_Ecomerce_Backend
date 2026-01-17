FROM php:8.3-fpm

# Ustaw argumenty
ARG DEBIAN_FRONTEND=noninteractive

# Instalacja zależności systemowych
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalacja rozszerzeń PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Utworzenie użytkownika www-data (jeśli nie istnieje)
RUN groupadd -g 1000 www || true
RUN useradd -u 1000 -ms /bin/bash -g www www || true

# Ustawienie katalogu roboczego
WORKDIR /var/www

# Kopiowanie plików composer do instalacji zależności (cache layer optimization)
COPY --chown=www:www composer.json composer.lock* ./

# Instalacja zależności PHP (jeśli composer.json istnieje)
RUN if [ -f composer.json ]; then composer install --no-dev --no-scripts --prefer-dist --no-interaction || true; fi

# Kopiowanie pozostałych plików aplikacji
COPY --chown=www:www . /var/www

# Instalacja zależności deweloperskich i skryptów (jeśli potrzebne)
RUN if [ -f composer.json ]; then composer install --no-interaction || composer update --no-interaction || true; fi

# Utworzenie katalogów storage i cache jeśli nie istnieją
RUN mkdir -p /var/www/storage/app/public \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache

# Ustawienie uprawnień dla storage i cache
RUN chown -R www:www /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Użytkownik do uruchomienia procesów
USER www

# Expose port 9000 i uruchom php-fpm
EXPOSE 9000
CMD ["php-fpm"]
