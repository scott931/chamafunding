#!/bin/bash

# Entrypoint script that determines which service to run
# Based on RENDER_SERVICE_TYPE environment variable

if [ "$RENDER_SERVICE_TYPE" = "worker" ]; then
    echo "Starting as worker service..."
    exec bash scripts/start-worker.sh
else
    echo "Starting as web service..."
    exec bash scripts/start.sh
fi

