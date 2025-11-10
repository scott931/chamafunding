#!/bin/bash

# Post-deployment script for Render
# This script runs after the application is deployed

set -e

echo "Running post-deployment setup..."

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Create storage link
echo "Creating storage link..."
php artisan storage:link || true

# Cache configuration
echo "Caching configuration..."
php artisan config:cache

# Cache routes
echo "Caching routes..."
php artisan route:cache

# Cache views
echo "Caching views..."
php artisan view:cache

# Clear application cache
echo "Clearing application cache..."
php artisan cache:clear

echo "Post-deployment setup complete!"

