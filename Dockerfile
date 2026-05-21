FROM php:8.5-fpm

RUN apt-get update && apt-get install -y unzip git && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_mysql

ARG UID=1000
ARG GID=1000
RUN groupadd -g $GID appuser && useradd -l -m -u $UID -g $GID appuser

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-dev --optimize-autoloader

COPY index.php init.php Config.php ./
COPY App ./App/

RUN chown -R appuser:appuser /var/www/html

RUN sed -i 's/user = www-data/user = appuser/g' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/group = www-data/group = appuser/g' /usr/local/etc/php-fpm.d/www.conf

USER appuser
