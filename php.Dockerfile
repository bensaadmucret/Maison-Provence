FROM php:8.2-fpm-alpine

# Installation des dépendances système
RUN apk add --no-cache \
    postgresql-dev \
    git \
    zip \
    unzip \
    libzip-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    mariadb-dev

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    intl \
    zip \
    gd

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration de PHP
RUN mv "/usr/local/etc/php/php.ini-development" "/usr/local/etc/php/php.ini"

WORKDIR /var/www/html
