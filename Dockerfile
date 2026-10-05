# --- Base image: PHP 8.2 on Apache ---
FROM php:8.2-apache

# The database driver the app needs. Installed first, on its own, because
# nothing works without it.
RUN docker-php-ext-install pdo_mysql

# GD is used to resize uploads before they are stored. It needs these
# system libraries to compile (JPEG, PNG/zlib, WEBP and FreeType support).
# If this step ever fails, the build still succeeds - the upload code
# falls back to storing the original image untouched.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libjpeg62-turbo-dev \
        libpng-dev \
        zlib1g-dev \
        libwebp-dev \
        libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install gd \
    && rm -rf /var/lib/apt/lists/* \
    || echo "GD not installed - uploads will be stored at original size"

# Copy the whole project into the web root
COPY . /var/www/html/

# The official image already listens on port 80, which the host maps.

# www-data owns uploads so added-item photos can be written
RUN chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80
