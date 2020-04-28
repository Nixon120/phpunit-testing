FROM php:7.3-fpm-alpine

LABEL authors="Josh Houghtelin, Zechariah Walden, AllDigitalRewards"

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

RUN apk add --no-cache git curl bash libxml2-dev supervisor nginx gettext-dev autoconf icu-dev g++ make libzip-dev \
    gettext-dev \
    && rm -rf /var/cache/apk/*

RUN docker-php-ext-install pdo pdo_mysql bcmath exif sockets mbstring xml gettext \
    && docker-php-ext-configure zip --with-libzip=/usr/include \
    && docker-php-ext-install zip

RUN pecl install redis && docker-php-ext-enable redis

RUN mkdir -p /run/nginx
RUN mkdir -p /run/php
RUN mkdir -p /etc/supervisor.d

COPY nginx.conf /etc/nginx/nginx.conf
COPY php.ini /usr/local/etc/php/php.ini
COPY supervisord.conf /etc/supervisord.conf
COPY crontab /etc/crontab
COPY startup.sh /usr/local/bin/startup.sh

RUN /usr/bin/crontab /etc/crontab

# Copy app files and set permissions
RUN mkdir -p /app

COPY packaged/ /app
RUN chown -R www-data: /app

EXPOSE 80

CMD ["/usr/local/bin/startup.sh"]
