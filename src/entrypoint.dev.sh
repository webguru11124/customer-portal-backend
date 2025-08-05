#!/bin/bash

echo "Dev Entrypoint Script Started" &&

# Install composer packages
echo "composer install" &&
composer install &&

# Migrate
echo "php artisan migrate" &&
php artisan migrate &&

# Seed Defaults
echo "php artisan db:seed" &&
php artisan db:seed &&

# Run the apache web server
echo "php-fpm" &&
exec php-fpm

