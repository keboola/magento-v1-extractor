FROM php:5.6-cli

WORKDIR /code

RUN apt-get update && apt-get install -y \
        git \
        unzip \
        libmcrypt-dev \
   --no-install-recommends && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-install mcrypt

RUN curl -sS https://getcomposer.org/installer | php \
  && mv /code/composer.phar /usr/local/bin/composer

COPY . /code/
WORKDIR /code

RUN php -d memory_limit=-1 /usr/local/bin/composer install

CMD php ./src/run.php run ./data