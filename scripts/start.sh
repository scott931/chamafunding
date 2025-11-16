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

# Verify session directory is writable
if [ ! -w storage/framework/sessions ]; then
    echo "WARNING: Session directory is not writable, attempting to fix..."
    chmod 775 storage/framework/sessions
    chown www-data:www-data storage/framework/sessions
fi

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
echo "Clearing OPCache..."
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPCache cleared successfully\n'; } else { echo 'OPCache not available\n'; }" || true

# Also invalidate opcache for all files
php -r "if (function_exists('opcache_invalidate')) {
    \$files = get_included_files();
    foreach (\$files as \$file) {
        opcache_invalidate(\$file, true);
    }
    echo 'OPCache invalidated for loaded files\n';
}" || true
# =======
# # Optionally warm caches to speed up responses
# echo "Rebuilding optimized caches..."
# php artisan config:cache || true
# php artisan route:cache || true
# php artisan view:cache || true
# >>>>>>> main

# Wait for database to be ready and run migrations automatically
# This runs on every service start, including after deployment
echo "=========================================="
echo "Running automatic database migrations..."
echo "=========================================="
max_attempts=30
attempt=0
db_connected=false

# Test database connection using a simple PHP script
while [ $attempt -lt $max_attempts ]; do
    attempt=$((attempt + 1))
    
    # Try to connect using a simple PHP one-liner
    if php -r "
    try {
        \$host = getenv('DB_HOST');
        \$port = getenv('DB_PORT') ?: '5432';
        \$database = getenv('DB_DATABASE');
        \$username = getenv('DB_USERNAME');
        \$password = getenv('DB_PASSWORD');
        
        if (empty(\$host) || empty(\$database) || empty(\$username)) {
            throw new Exception('Database environment variables not set');
        }
        
        \$dsn = \"pgsql:host=\$host;port=\$port;dbname=\$database\";
        \$pdo = new PDO(\$dsn, \$username, \$password, [PDO::ATTR_TIMEOUT => 5]);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$pdo->query('SELECT 1');
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
    " 2>/dev/null; then
        echo "✓ Database connection successful!"
        db_connected=true
        break
    else
        echo "Database connection attempt $attempt/$max_attempts..."
        sleep 2
    fi
done

if [ "$db_connected" = true ]; then
    echo ""
    echo "Running database migrations (this happens automatically after each deployment)..."
    php artisan migrate --force
    migration_exit_code=$?
    if [ $migration_exit_code -eq 0 ]; then
        echo "✓ Migrations completed successfully!"
    else
        echo "⚠ WARNING: Migrations exited with code $migration_exit_code"
        echo "This might be normal if migrations were already up to date."
    fi
    echo ""
else
    echo ""
    echo "⚠ WARNING: Could not connect to database after $max_attempts attempts."
    echo "This might be normal if the database is still provisioning."
    echo "The application will continue, but database features may not work until the database is ready."
    echo "Migrations will be retried on the next service restart."
    echo ""
fi
echo "=========================================="

# Create storage link if it doesn't exist (non-blocking)
echo "Creating storage link..."
php artisan storage:link || true

# Start PHP server immediately (this must run in foreground)
echo "Starting PHP server on 0.0.0.0:$PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

