# Use a Debian-based Swoole image for better package stability and mirror reliability
FROM phpswoole/swoole:6.0-php8.3

WORKDIR /var/www/html

# # Install system dependencies (Debian based)
# # Re-enabled these as they are required for Octane and Laravel to run.
# RUN apt-get update && apt-get install -y \
#     git \
#     unzip \
#     libzip-dev \
#     libpng-dev \
#     libjpeg-dev \
#     libfreetype6-dev \
#     libicu-dev \
#     libonig-dev \
#     libxml2-dev \
#     && apt-get clean && rm -rf /var/lib/apt/lists/*

# # Install PHP extensions required by Laravel
RUN docker-php-ext-install \
    pcntl \
    bcmath

# # Copy application files (Copying your local vendor and public/build)
# COPY . .

# Give ownership to the Swoole user and set permissions
# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose Octane port
EXPOSE 8000

# Start Laravel Octane with Swoole directly, configured with 6 workers
# Note: Since composer install is removed, you must run it locally first.
CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=6"]
