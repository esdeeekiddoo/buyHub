# --- Base image: PHP 8.2 on Apache ---
FROM php:8.2-apache

# PDO MySQL driver, plus GD for resizing uploads before they are stored.
RUN docker-php-ext-install pdo_mysql gd

# Copy the whole project into the web root
COPY . /var/www/html/

# The official image already listens on port 80, which the host maps.

# www-data owns uploads so added-item photos can be written
RUN chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
