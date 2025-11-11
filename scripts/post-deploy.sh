#!/bin/bash

# Post-deployment script for Render
# This script runs after the application is deployed

set -e

echo "Running post-deployment setup..."

# Clear all caches first to ensure latest changes are reflected
echo "Clearing all caches..."
php artisan optimize:clear || true
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Create storage link
echo "Creating storage link..."
php artisan storage:link || true

# Note: We don't cache views/config/routes in production to ensure changes are immediately visible
# If you need performance optimization, you can uncomment these after confirming everything works:
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

echo "Post-deployment setup complete!"

