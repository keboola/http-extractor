FROM php:7.1-cli

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /code

RUN apt-get update && apt-get install -y \
        git \
        unzip \
   --no-install-recommends && rm -r /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php \
  && mv /code/composer.phar /usr/local/bin/composer

COPY . /code/
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

RUN composer install --no-interaction

CMD php /code/src/run.php
