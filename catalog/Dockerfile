FROM php:8.3-fpm-bullseye AS build

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/catalog

COPY . .

RUN composer install --no-dev --prefer-dist --optimize-autoloader
RUN php artisan config:clear

FROM php:8.3-fpm-bullseye AS runtime

RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/catalog

COPY --from=build /var/www/catalog/vendor ./vendor
COPY --from=build /var/www/catalog/app ./app
COPY --from=build /var/www/catalog/bootstrap ./bootstrap
COPY --from=build /var/www/catalog/config ./config
COPY --from=build /var/www/catalog/public ./public
COPY --from=build /var/www/catalog/database/migrations ./database/migrations
COPY --from=build /var/www/catalog/artisan ./artisan

COPY docker/startup.sh /startup.sh
RUN chmod +x /startup.sh

EXPOSE 8000

HEALTHCHECK --interval=10s --timeout=3s --start-period=5s --retries=3 \
  CMD php -r "$fp=@fsockopen('127.0.0.1',8000,$e,$s,2);if(!$fp){exit(1);}fwrite($fp,\"GET /up HTTP/1.1\\r\\nHost: localhost\\r\\nConnection: close\\r\\n\\r\\n\");$resp=stream_get_contents($fp);fclose($fp);exit(strpos($resp,\"HTTP/1.1 200\")===false);"

ENTRYPOINT ["/startup.sh"]
