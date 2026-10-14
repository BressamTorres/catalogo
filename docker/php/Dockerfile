FROM php:8.4-fpm

ENV TZ=America/Sao_Paulo

RUN echo "date.timezone=${TZ}" > /usr/local/etc/php/conf.d/timezone.ini

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libicu-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        bcmath \
        pcntl \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
