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

# Manually remove compiled views to ensure they're regenerated
echo "Removing compiled views..."
rm -rf storage/framework/views/*.php 2>/dev/null || true

# Clear bootstrap cache
echo "Clearing bootstrap cache..."
rm -rf bootstrap/cache/*.php 2>/dev/null || true
# Keep the .gitignore file
touch bootstrap/cache/.gitignore 2>/dev/null || true

# Aggressively clear OPCache - critical for seeing changes immediately after deployment
echo "Aggressively clearing OPCache..."
php -r "
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo '✓ OPCache reset successfully\n';
} else {
    echo '⚠ OPCache not available\n';
}

// Invalidate all cached files in common directories
if (function_exists('opcache_invalidate')) {
    \$dirs = [
        '/var/www/html/app',
        '/var/www/html/config',
        '/var/www/html/routes',
        '/var/www/html/resources/views',
        '/var/www/html/Modules'
    ];
    
    \$invalidated = 0;
    foreach (\$dirs as \$dir) {
        if (is_dir(\$dir)) {
            \$iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(\$dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach (\$iterator as \$file) {
                if (\$file->isFile() && \$file->getExtension() === 'php') {
                    if (opcache_invalidate(\$file->getRealPath(), true)) {
                        \$invalidated++;
                    }
                }
            }
        }
    }
    echo \"✓ Invalidated OPCache for \$invalidated PHP files\n\";
}
" || true

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

