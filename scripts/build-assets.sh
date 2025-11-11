#!/bin/bash
set -e

echo "========================================="
echo "Building Application Assets"
echo "========================================="

# Build main application assets
echo ""
echo "📦 Building main application assets..."
npm ci
npm run build
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
        cd "Modules/$module"

        if [ -f "package-lock.json" ]; then
            npm ci --silent
        else
            npm install --silent
        fi

        if npm run build; then
            echo "  ✅ $module built successfully"
            BUILD_COUNT=$((BUILD_COUNT + 1))
        else
            echo "  ❌ $module build failed"
            ERROR_COUNT=$((ERROR_COUNT + 1))
        fi

        cd ../..
    else
        echo "  ⏭️  Skipping $module (no assets to build)"
    fi
done

echo ""
echo "========================================="
echo "Build Summary"
echo "========================================="
echo "✅ Successfully built: $BUILD_COUNT modules"
if [ $ERROR_COUNT -gt 0 ]; then
    echo "❌ Failed: $ERROR_COUNT modules"
    exit 1
fi
echo ""
echo "🎉 All assets built successfully!"

