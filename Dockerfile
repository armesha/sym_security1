FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    zip

# Enable Apache modules
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Set environment variable to allow Composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies
RUN composer install --no-interaction --no-scripts

# Copy the rest of the application
COPY . .

# Run Composer scripts now that we have the full application
RUN composer run-script post-install-cmd

# Set permissions
RUN chown -R www-data:www-data var/
RUN chmod -R 777 var/

# Configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Configure Apache virtual host
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot ${APACHE_DOCUMENT_ROOT}\n\
    DirectoryIndex index.php\n\
    <Directory ${APACHE_DOCUMENT_ROOT}>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf
