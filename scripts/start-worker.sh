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
        echo "Database connection successful!"
        db_connected=true
        break
    else
        # Show error message on first and every 5th attempt
        if [ $attempt -eq 1 ] || [ $((attempt % 5)) -eq 0 ]; then
            echo "Database is unavailable - sleeping (attempt $attempt/$max_attempts) ($(echo "$connection_result" | grep -o 'ERROR:.*' | head -1 || echo 'Connection failed'))"
        else
            echo "Database is unavailable - sleeping (attempt $attempt/$max_attempts)"
        fi
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

