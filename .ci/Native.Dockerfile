FROM php:8.3.35-cli-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libzip-dev libonig-dev libpng-dev libxml2-dev \
    && docker-php-ext-install intl mbstring zip gd pdo_mysql \
    && rm -rf /var/lib/apt/lists/*
