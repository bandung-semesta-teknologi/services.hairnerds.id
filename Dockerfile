FROM dunglas/frankenphp:1.9-builder-php8.4

WORKDIR /app

RUN apt-get update \
    && apt-get install -y apt-transport-https curl gnupg2 libpng-dev libzip-dev nano unzip libpq-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN docker-php-ext-install -j$(nproc) \
    bcmath mbstring exif pcntl curl xml zip intl gd dom sockets opcache

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install

RUN chown -R www-data:www-data /app/storage/ /app/bootstrap/cache

EXPOSE 80 443 443/udp

CMD ["frankenphp", "run"]
