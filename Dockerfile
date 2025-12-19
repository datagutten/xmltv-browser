FROM php:8.3 AS builder
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y libcurl4-openssl-dev unzip git
RUN docker-php-ext-install curl

COPY . /app
COPY config_env.php /app/config.php
WORKDIR /app

RUN composer install --no-dev

FROM php:8.3-apache
COPY --from=builder /app /var/www/html/
RUN apt-get update && apt-get install -y libcurl4-openssl-dev
RUN docker-php-ext-install curl

ENV XMLTV_PATH=/xmltv
