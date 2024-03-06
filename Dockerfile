FROM php:8-fpm

RUN apt-get update -y && apt-get install iputils-ping -y

# Install XDebug 
RUN pecl install xdebug && docker-php-ext-enable xdebug 

# Install needed php extensions: mysql
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN docker-php-ext-install pdo pdo_mysql

# Install OS dependencies
RUN set -ex; \
        apt-get update && \
        DEBIAN_FRONTEND=noninteractive \ 
        apt-get install --no-install-recommends -y \
        libldap2-dev \
        libfreetype6-dev \
        libjpeg-dev \
        libonig-dev \
        libc-client-dev \
        libkrb5-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        netcat \
        && apt-get -y autoclean; apt-get -y autoremove; \
        rm -rf /var/lib/apt/lists/*
