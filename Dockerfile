
FROM php:8.4-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install system deps + build deps for PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
      gd \
      mysqli \
      pdo \
      pdo_mysql \
      zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
