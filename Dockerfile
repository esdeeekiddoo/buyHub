# --- Base image: PHP 8.2 on Apache ---
FROM php:8.2-apache

# PDO MySQL driver, same one Laragon ships
RUN docker-php-ext-install pdo_mysql

# Copy the whole project into the web root
COPY . /var/www/html/

# Apache serves from /var/www/html. Render injects $PORT at runtime, and
# the official image already maps port 80. For Render that is enough, but
# a one-liner keeps the config portable:
#   nothing to change - Render proxies to the container port.

# www-data owns uploads so added-item photos can be written
RUN chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
