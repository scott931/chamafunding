#!/bin/bash
# Don't use set -e here, we want to handle errors manually
set +e

echo "========================================="
echo "Building Application Assets"
echo "========================================="

# Build main application assets
echo ""
echo "📦 Building main application assets..."
echo "Current directory: $(pwd)"
echo "Node version: $(node --version)"
echo "NPM version: $(npm --version)"

# Check if package-lock.json exists
if [ -f "package-lock.json" ]; then
    echo "Found package-lock.json, running npm ci..."
    npm ci
    if [ $? -ne 0 ]; then
        echo "❌ npm ci failed, trying npm install instead..."
        npm install
        if [ $? -ne 0 ]; then
            echo "❌ npm install also failed!"
            exit 1
        fi
    fi
else
    echo "⚠️  No package-lock.json found, running npm install..."
    npm install
    if [ $? -ne 0 ]; then
        echo "❌ npm install failed!"
        exit 1
    fi
fi

echo "Running npm run build..."
npm run build
if [ $? -ne 0 ]; then
    echo "❌ Main application build failed!"
    exit 1
fi
echo "✅ Main application assets built successfully"

# Read module statuses
if [ ! -f "modules_statuses.json" ]; then
    echo "⚠️  modules_statuses.json not found, building all modules"
    MODULES="Admin Crowdfunding Finance Notifications Payments Reports Savings Subscriptions UserManagement"
else
    # Extract enabled modules from JSON (simple grep approach)
    MODULES=$(grep -o '"[^"]*":\s*true' modules_statuses.json | cut -d'"' -f2 | tr '\n' ' ')
fi

echo ""
echo "📦 Building module assets..."
BUILD_COUNT=0
ERROR_COUNT=0

for module in $MODULES; do
    if [ -d "Modules/$module" ] && [ -f "Modules/$module/package.json" ] && [ -f "Modules/$module/vite.config.js" ]; then
        echo ""
        echo "  Building $module..."
        echo "  Directory: Modules/$module"
        cd "Modules/$module"

        if [ -f "package-lock.json" ]; then
            echo "  Running npm ci..."
            npm ci
            if [ $? -ne 0 ]; then
                echo "  ⚠️  npm ci failed for $module, trying npm install..."
                npm install
            fi
        else
            echo "  Running npm install..."
            npm install
        fi

        echo "  Running npm run build for $module..."
        if npm run build; then
            echo "  ✅ $module built successfully"
            BUILD_COUNT=$((BUILD_COUNT + 1))
        else
            echo "  ❌ $module build failed"
            ERROR_COUNT=$((ERROR_COUNT + 1))
            # Don't exit on module build failure, continue with other modules
        fi

        cd ../..
    else
        echo "  ⏭️  Skipping $module (no assets to build)"
        if [ ! -d "Modules/$module" ]; then
            echo "    Reason: Directory not found"
        elif [ ! -f "Modules/$module/package.json" ]; then
            echo "    Reason: package.json not found"
        elif [ ! -f "Modules/$module/vite.config.js" ]; then
            echo "    Reason: vite.config.js not found"
        fi
    fi
done

echo ""
echo "========================================="
echo "Build Summary"
echo "========================================="
echo "✅ Successfully built: $BUILD_COUNT modules"
if [ $ERROR_COUNT -gt 0 ]; then
    echo "❌ Failed: $ERROR_COUNT modules"
    echo "⚠️  Warning: Some module builds failed, but continuing..."
    # Don't exit with error - allow build to continue
    # exit 1
fi
echo ""
echo "🎉 Asset build process completed!"
echo "Build output directories:"
ls -la public/build* 2>/dev/null || echo "No build directories found in public/"

