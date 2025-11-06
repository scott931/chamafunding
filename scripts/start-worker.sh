#!/bin/bash

# Startup script for Render queue worker
# This script starts the queue worker

set -e

echo "Starting ChamaFunding queue worker..."

# Wait for database to be ready
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

# Start the queue worker
echo "Starting queue worker..."
exec php artisan queue:work --tries=3 --timeout=90

