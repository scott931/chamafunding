#!/bin/bash

# Startup script for Render deployment
# This script starts the application immediately and runs setup in background

echo "Starting ChamaFunding application..."

# Get the port from environment variable (Render sets this automatically)
PORT=${PORT:-10000}
echo "Using port: $PORT"

# Ensure cache/session directories exist with correct permissions
echo "Preparing cache directories..."
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear all caches to ensure latest changes are reflected
echo "Clearing all caches..."
php artisan optimize:clear || true
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Optionally warm caches to speed up responses
echo "Rebuilding optimized caches..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Create storage link if it doesn't exist (non-blocking)
echo "Creating storage link..."
php artisan storage:link || true

# Start PHP server immediately (this must run in foreground)
echo "Starting PHP server on 0.0.0.0:$PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

