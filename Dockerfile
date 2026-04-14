FROM php:8.2-fpm-alpine

# Без docker-php-ext-install: на слабом VPS компиляция opcache/zip/intl занимает много минут.
# Официальный образ уже содержит нужные модули (в т.ч. opcache, mbstring, json).
# Composer ставит зависимости через бинарник unzip.
RUN apk add --no-cache git unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
