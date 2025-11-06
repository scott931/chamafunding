#!/bin/bash

# Startup script for Render deployment
# This script runs migrations and then starts the application

echo "Starting ChamaFunding application..."

# Wait a moment for database to be ready
echo "Waiting for services to be ready..."
sleep 5

# Run database migrations (don't fail if this errors)
echo "Running database migrations..."
php artisan migrate --force || echo "Migration failed, continuing anyway..."

# Create storage link if it doesn't exist
echo "Creating storage link..."
php artisan storage:link || true

# Cache configuration for better performance (don't fail if this errors)
echo "Caching configuration..."
php artisan config:cache || echo "Config cache failed, continuing..."
php artisan route:cache || echo "Route cache failed, continuing..."
php artisan view:cache || echo "View cache failed, continuing..."

# Start the application (this must succeed)
echo "Starting PHP server on port ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}

