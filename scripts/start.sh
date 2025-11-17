#!/bin/bash

# Startup script for Render deployment
# This script starts the application immediately and runs setup in background

echo "Starting ChamaFunding application..."

# Verify critical environment variables are set
echo "Verifying environment variables..."
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY environment variable is not set!"
    echo "This will cause encryption errors. Please set APP_KEY in Render environment variables."
    exit 1
fi
echo "✓ APP_KEY is set"

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

# Verify APP_KEY is available to PHP (critical for encryption)
echo "Verifying APP_KEY is available to PHP..."
php -r "if (empty(getenv('APP_KEY'))) { echo 'ERROR: APP_KEY not available to PHP!\n'; exit(1); } else { echo '✓ APP_KEY is available to PHP\n'; }"

# Remove .env file if it exists to ensure environment variables from Render are used
# Laravel's env() function reads .env first, which can override actual environment variables
if [ -f .env ]; then
    echo "Removing .env file to ensure Render environment variables are used..."
    rm -f .env
    echo "✓ .env file removed"
fi


# Manually remove compiled views to ensure they're regenerated
echo "Removing compiled views..."
rm -rf storage/framework/views/*.php 2>/dev/null || true

# Clear bootstrap cache
echo "Clearing bootstrap cache..."
rm -rf bootstrap/cache/*.php 2>/dev/null || true
# Keep the .gitignore file
touch bootstrap/cache/.gitignore 2>/dev/null || true

# Aggressively clear OPCache - this is critical for seeing changes immediately
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
    
    # Try to connect using a simple PHP one-liner with better error handling
    connection_result=$(php -r "
    try {
        \$host = getenv('DB_HOST');
        \$port = getenv('DB_PORT') ?: '5432';
        \$database = getenv('DB_DATABASE');
        \$username = getenv('DB_USERNAME');
        \$password = getenv('DB_PASSWORD');
        
        if (empty(\$host) || empty(\$database) || empty(\$username)) {
            echo 'ERROR: Database environment variables not set';
            exit(1);
        }
        
        // Increase timeout for initial connection (database might be slow to start)
        \$dsn = \"pgsql:host=\$host;port=\$port;dbname=\$database;connect_timeout=10\";
        \$pdo = new PDO(\$dsn, \$username, \$password, [
            PDO::ATTR_TIMEOUT => 10,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false
        ]);
        
        // Test the connection with a simple query
        \$pdo->query('SELECT 1');
        echo 'SUCCESS';
        exit(0);
    } catch (PDOException \$e) {
        echo 'ERROR: ' . \$e->getMessage();
        exit(1);
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
        exit(1);
    }
    " 2>&1)
    
    if echo "$connection_result" | grep -q "SUCCESS"; then
        echo "✓ Database connection successful!"
        db_connected=true
        break
    else
        # Show error message on first and every 5th attempt
        if [ $attempt -eq 1 ] || [ $((attempt % 5)) -eq 0 ]; then
            echo "Database connection attempt $attempt/$max_attempts... ($(echo "$connection_result" | grep -o 'ERROR:.*' | head -1 || echo 'Connection failed'))"
        else
            echo "Database connection attempt $attempt/$max_attempts..."
        fi
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

