#!/bin/bash
set -e

echo "PROD Entrypoint Script Started" &&

# Clear cache
echo "php artisan optimize:clear" &&
php artisan optimize:clear &&

# Run the apache web server
echo "php-fpm" &&
exec php-fpm

