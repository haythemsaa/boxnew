#!/bin/bash

#==============================================================================
# BoxiBox Simple Deployment Script
# Server: 2emeservice.be
#==============================================================================

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}=== BoxiBox Deployment ===${NC}"

# Navigate to project directory
cd /var/www/vhosts/2emeservice.be/httpdocs/boxnew/boxibox-app

echo "1. Pulling latest code..."
git pull origin main

echo "2. Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "3. Installing NPM dependencies..."
npm ci

echo "4. Building assets..."
npm run build

echo "5. Running migrations..."
php artisan migrate --force

echo "6. Clearing caches..."
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "7. Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo -e "${GREEN}=== Deployment Complete! ===${NC}"
