FROM ubuntu:16.04

MAINTAINER Josh Houghtelin

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

RUN apt-get update \
    && apt-get install -y software-properties-common language-pack-en locales \
    && add-apt-repository ppa:ondrej/php \
    && locale-gen en_US en_US.UTF-8

RUN apt-get update \
    && apt-get install -y cron nginx curl zip unzip git supervisor libmemcached-dev zlib1g-dev libicu-dev g++ \
    && apt-get install -y php7.2-fpm php7.2-cli php7.2-gd php7.2-mysql php-memcached \
       php7.2-mbstring php7.2-xml php7.2-curl php7.2-bcmath php7.2-intl php7.2-zip php-xdebug \
       php-mongodb php7.2-dev \
    && mkdir /run/php \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && echo "daemon off;" >> /etc/nginx/nginx.conf

RUN git clone git://github.com/xdebug/xdebug.git && cd xdebug && phpize && ./configure --enable-xdebug && make && make install

# Copy configuration files
COPY nginx.conf /etc/nginx/sites-available/default
COPY php-fpm.conf /etc/php/7.2/fpm/php-fpm.conf
COPY www.conf /etc/php/7.2/fpm/pool.d/www.conf
COPY php.ini /etc/php/7.2/fpm/php.ini
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY crontab /etc/crontab

# Copy container entry point script.
COPY startup.sh /usr/local/bin/startup.sh

# Copy app files and set permissions.
COPY packaged/ /var/www/html/
RUN chown -R www-data: /var/www/html

EXPOSE 80

CMD ["/usr/local/bin/startup.sh"]

