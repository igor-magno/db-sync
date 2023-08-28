FROM php:8.1-apache

WORKDIR /var/www/html

COPY . .

RUN a2enmod rewrite

RUN docker-php-ext-install pdo pdo_mysql