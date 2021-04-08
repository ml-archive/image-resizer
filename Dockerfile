FROM composer:1.9 as intermediate

ENV COMPOSER_CACHE_DIR=/tmp/.composer

# Copy composer files
COPY composer.json /var/www/app/composer.json
COPY composer.lock /var/www/app/composer.lock

# Make the app the pwd
WORKDIR /var/www/app

RUN composer install --ignore-platform-reqs --no-interaction --no-scripts --no-autoloader

ENV DEBIAN_FRONTEND=noninteractive \
    NGINX_VERSION=1.14.0-1~stretch \
    SERVER_NAME=_

FROM php:7.3-fpm

RUN apt-get -y update
RUN apt-get install -y -qq \
    curl \
    cron \
    gettext \
    git \
    gnupg \
    libcurl3-dev \
    libmcrypt-dev \
    libpng-dev \
    libssl-dev \
    libxml2-dev \
    libzip-dev \
    supervisor \
    wget \
    vim \
    libonig-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# install system dependencies
RUN docker-php-ext-install -j$(nproc) \
    bcmath \
    curl \
    gd \
    json \
    mbstring \
    opcache \
    pdo_mysql \
    xml \
    zip

RUN apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys 573BFD6B3D8FBC641079A6ABABF5BD827BD9BF62 \
    && echo "deb http://nginx.org/packages/debian/ stretch nginx" >> /etc/apt/sources.list \
    && apt-get update

RUN apt-get install --no-install-recommends --no-install-suggests -q -y \
    nginx=1.14.0-1~stretch

RUN apt-get update && apt-get install -y \
    libmagickwand-dev --no-install-recommends \
    && pecl install imagick \
	&& docker-php-ext-enable imagick
    
# Whitelist ALLOWED_HOSTS and APP_ENV in fpm config
RUN { \
      echo 'env[ALLOWED_HOSTS] = $ALLOWED_HOSTS'; \
      echo 'env[APP_ENV] = $APP_ENV'; \
    } >> /usr/local/etc/php-fpm.d/www.conf && \
    sed -i "s/\# server_tokens off\;/server_tokens off\;/" /etc/nginx/nginx.conf

RUN wget https://getcomposer.org/download/1.9.3/composer.phar && \
    mv composer.phar /usr/local/bin/composer && \
    chmod a+x /usr/local/bin/composer

COPY --from=intermediate --chown=www-data:www-data /var/www/app/vendor /var/www/app/vendor

RUN rm /etc/nginx/conf.d/default.conf
COPY --chown=www-data:www-data default.conf /etc/nginx/conf.d

COPY supervisord.conf /etc/supervisor/conf.d/supervisor.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisor.conf"]

# Use unprivileged user
WORKDIR /var/www/app

# Add code
COPY --chown=www-data:www-data . /var/www/app

RUN composer dump-autoload --optimize
