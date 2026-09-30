# Imagem PHP da aplicação: Nginx + PHP-FPM (serversideup) com as extensões que o projeto exige.
# Estágios: base (desenvolvimento, código montado por volume no compose) e production (deploy, código na imagem).
FROM serversideup/php:8.4-fpm-nginx AS base

USER root

# bcmath: aritmética decimal exata na conversão de moeda (centavos x cotação sem passar por float)
RUN install-php-extensions bcmath

USER www-data

# Dependências PHP de produção. Também alimentam o build dos assets: o app.js importa o Livewire de vendor/
FROM base AS vendor

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

# Assets do Vite (CSS e JS) gerados no build: produção não roda o servidor do Vite
FROM node:24-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build

# Último estágio: é o que o Railway constrói por padrão
FROM base AS production

# Na inicialização a imagem roda "artisan optimize" (cache de config, rotas, views e eventos) com as variáveis do
# ambiente. A migration fica fora: roda uma única vez no preDeployCommand do Railway, não a cada container.
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    PHP_OPCACHE_ENABLE=1

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /var/www/html/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN composer dump-autoload --no-dev --optimize
