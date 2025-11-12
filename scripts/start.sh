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

# Manually remove compiled views to ensure they're regenerated
echo "Removing compiled views..."
rm -rf storage/framework/views/*.php 2>/dev/null || true

# Clear bootstrap cache
echo "Clearing bootstrap cache..."
rm -rf bootstrap/cache/*.php 2>/dev/null || true
# Keep the .gitignore file
touch bootstrap/cache/.gitignore 2>/dev/null || true

# Clear opcache if available (for PHP-FPM or mod_php)
if [ -f /usr/local/bin/php ]; then
    echo "Attempting to clear opcache..."
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'Opcache cleared\n'; }" || true
fi

# Create storage link if it doesn't exist (non-blocking)
echo "Creating storage link..."
php artisan storage:link || true

# Start PHP server immediately (this must run in foreground)
echo "Starting PHP server on 0.0.0.0:$PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

