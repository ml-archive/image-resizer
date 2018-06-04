FROM 1and1internet/ubuntu-16-nginx-php-7.0:latest

ENV COMPOSER_CACHE_DIR=/tmp/.composer

# Whitelist ALLOWED_HOSTS and APP_ENV in fpm config
RUN { \
      echo 'env[ALLOWED_HOSTS] = $ALLOWED_HOSTS'; \
      echo 'env[APP_ENV] = $APP_ENV'; \
    } >> /etc/php/7.0/fpm/pool.d/www.conf && \
    sed -i "s/\# server_tokens off\;/server_tokens off\;/" /etc/nginx/nginx.conf

# Use unprivileged user
USER 999

# Copy composer files
COPY --chown=999 composer.json composer.lock /var/www/html/

# Make the app the pwd
WORKDIR /var/www/html

# Install dependencies
RUN composer install --no-dev --no-interaction --no-autoloader

# Add code
COPY --chown=999 . /var/www/html

RUN composer dump-autoload --optimize
