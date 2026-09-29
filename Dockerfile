# Imagem PHP da aplicação: Nginx + PHP-FPM (serversideup) com as extensões que o projeto exige.
FROM serversideup/php:8.4-fpm-nginx AS base

USER root

# bcmath: aritmética decimal exata na conversão de moeda (centavos x cotação sem passar por float)
RUN install-php-extensions bcmath

USER www-data
