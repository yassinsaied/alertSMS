FROM php:8.3-cli

# Installer les extensions PHP nécessaires pour PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["tail", "-f", "/dev/null"]