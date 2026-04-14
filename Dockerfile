FROM php:8.2-fpm-alpine

# intl (ICU) на слабом VPS долго компилируется; для Twig + HttpFoundation он не нужен
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install opcache zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
