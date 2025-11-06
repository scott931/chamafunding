#!/bin/bash

# Startup script for Render deployment
# This script runs migrations and then starts the application

set -e

echo "Starting ChamaFunding application..."

# Wait for database to be ready (optional, but helpful)
echo "Waiting for database connection..."
max_attempts=30
attempt=0
until php artisan migrate:status &> /dev/null || [ $attempt -ge $max_attempts ]; do
    attempt=$((attempt + 1))
    echo "Database is unavailable - sleeping (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ $attempt -ge $max_attempts ]; then
    echo "Warning: Could not verify database connection, proceeding anyway..."
else
    echo "Database is ready!"
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Create storage link if it doesn't exist
echo "Creating storage link..."
php artisan storage:link || true

# Cache configuration for better performance
echo "Caching configuration..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start the application
echo "Starting PHP server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-80}

