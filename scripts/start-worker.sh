#!/bin/bash

# Startup script for Render queue worker
# This script starts the queue worker

set -e

echo "Starting ChamaFunding queue worker..."

# Wait for database to be ready (optional for worker - it can start without DB)
echo "Waiting for database connection..."
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
        echo "Database connection successful!"
        db_connected=true
        break
    else
        echo "Database is unavailable - sleeping (attempt $attempt/$max_attempts)"
        sleep 2
    fi
done

if [ "$db_connected" != true ]; then
    echo "Warning: Could not verify database connection, proceeding anyway..."
    echo "Queue worker will start but may fail if database is not ready."
fi

# Start the queue worker
echo "Starting queue worker..."
exec php artisan queue:work --tries=3 --timeout=90

