FROM public.ecr.aws/composer/composer:2.5.5 as vendor

ARG repman_token
WORKDIR /build
COPY src/composer.* ./
RUN composer config --global --auth http-basic.aptive.repo.repman.io token ${repman_token}
RUN composer install \
    -v \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

FROM public.ecr.aws/docker/library/php:8.2-fpm

RUN apt update \
        && apt install -y \
            git \
            g++ \
            libfcgi-bin \
            libicu-dev \
            libpq-dev \
            libzip-dev \
            libpng-dev \
            zlib1g-dev \
            libxrender1 \
            libfontconfig1 \
            libxtst6 \
            unzip \
            xvfb \
        && docker-php-ext-install \
            intl \
            opcache \
            pdo \
            pdo_mysql \
            zip \
            gd\
            bcmath

# Needed for phpunit code coverage reports
RUN pecl install pcov apcu

# Enable the phpunit code coverage driver
RUN docker-php-ext-enable pcov apcu

# Install and enable redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# data dog
RUN curl -LO https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php
RUN php datadog-setup.php --php-bin=all

COPY docker/php/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf

COPY --from=public.ecr.aws/composer/composer:2.5.5 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/app
COPY --from=vendor /build .

COPY src/ .

RUN mkdir /var/www/.composer \
    && chown -R www-data:www-data /var/www/.composer \
    && chown -R www-data:www-data .

USER www-data

VOLUME /var/www/.composer

#RUN composer install
# include the commit hash as a variable.  This is most applicable in a ci environment
ARG CI_COMMIT_SHA
ENV CI_COMMIT_SHA=$CI_COMMIT_SHA
# CMD php artisan migrate --force && php artisan db:seed --force && php-fpm
CMD ["/var/www/app/entrypoint.prod.sh"]

HEALTHCHECK --interval=30s --timeout=3s --start-period=60s CMD ./php-fpm-healthcheck --listen-queue=10 --accepted-conn=3000 || exit 1
