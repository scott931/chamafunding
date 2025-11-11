#!/bin/bash

# Startup script for Render deployment
# This script starts the application immediately and runs setup in background

echo "Starting ChamaFunding application..."

# Get the port from environment variable (Render sets this automatically)
PORT=${PORT:-10000}
echo "Using port: $PORT"

# Clear all caches to ensure latest changes are reflected
echo "Clearing all caches..."
php artisan optimize:clear || true
php artisan cache:clear || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Create storage link if it doesn't exist (non-blocking)
echo "Creating storage link..."
php artisan storage:link || true

# Start PHP server immediately (this must run in foreground)
echo "Starting PHP server on 0.0.0.0:$PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

