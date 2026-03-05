FROM php:8.2-apache

# Instala mysqli
RUN docker-php-ext-install mysqli