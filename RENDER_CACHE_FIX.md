# Render Deployment Cache Issues - Fix Guide

## Problem
Changes deployed to Render are not reflecting on the live site. This is typically caused by:
1. **Docker layer caching** - Old files cached in Docker layers
2. **OPCache** - PHP opcode cache serving old compiled code
3. **Laravel caches** - Config, routes, views cached
4. **Render not rebuilding** - Using cached Docker images

## Quick Fixes

### Option 1: Force Rebuild on Render (Recommended)

1. **Go to Render Dashboard**
   - Navigate to your `chamafunding-web` service
   - Click on "Manual Deploy" → "Clear build cache & deploy"
   - This forces a complete rebuild without using cached layers

2. **Or via Render CLI:**
   ```bash
   render services:deploy chamafunding-web --clear-cache
   ```

### Option 2: Clear Caches via Browser Route

If you've enabled the cache clearing route in production:

1. **Enable the route** (one-time setup):
   - In Render Dashboard → Environment Variables
   - Add: `ALLOW_CACHE_CLEAR_ROUTE=true`
   - Save and redeploy

2. **Visit the cache clearing endpoint:**
   ```
   https://chamafunding-web.onrender.com/dev/clear-cache
   ```
   This will clear all Laravel caches and OPCache.

### Option 3: Manual Cache Clear via Render Shell

1. **Open Render Shell:**
   - Go to Render Dashboard → Your Service → Shell
   - Or use: `render shell chamafunding-web`

2. **Run cache clearing commands:**
   ```bash
   php artisan optimize:clear
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear

   # Clear OPCache
   php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPCache cleared\n'; }"
   ```

3. **Restart the service:**
   - In Render Dashboard → Click "Manual Deploy" → "Deploy latest commit"
   - This restarts the container and clears OPCache

## What Was Fixed

### 1. Dockerfile Updates
- **OPCache Configuration**: Added proper OPCache settings to validate timestamps
  - `opcache.validate_timestamps=1` - Check file changes
  - `opcache.revalidate_freq=0` - Check immediately (no delay)
  - This allows OPCache to detect file changes without restart

### 2. Enhanced Start Script
- **Aggressive Cache Clearing**: The `start.sh` script now:
  - Clears all Laravel caches on every container start
  - Clears OPCache on startup
  - Invalidates OPCache for all loaded files

### 3. Production Cache Clearing Route
- **Accessible Route**: `/dev/clear-cache` can now be enabled in production
  - Set `ALLOW_CACHE_CLEAR_ROUTE=true` in environment variables
  - Provides JSON response with cache clearing status
  - Clears both Laravel caches and OPCache

## Prevention Strategies

### 1. Always Force Rebuild After Major Changes
When you make significant code changes:
- Use "Clear build cache & deploy" in Render Dashboard
- Or add a comment to trigger a fresh build: `git commit --allow-empty -m "Force rebuild"`

### 2. Use Cache Busting for Assets
The application already uses:
- Vite hash-based filenames for CSS/JS
- Asset versioning in `config/app.php`
- Automatic cache busting in local environment

### 3. Monitor Deployment Logs
Check Render deployment logs to ensure:
- Caches are being cleared (look for "Clearing all caches...")
- OPCache is being reset
- No errors during cache clearing

## Troubleshooting

### Changes Still Not Appearing?

1. **Verify the deployment completed:**
   - Check Render Dashboard → Deployments
   - Ensure the latest deployment shows "Live"
   - Check build logs for errors

2. **Check if files were actually updated:**
   ```bash
   # In Render Shell
   cat /var/www/html/path/to/your/file.php
   # Verify the content matches your latest changes
   ```

3. **Verify OPCache is working:**
   ```bash
   # In Render Shell
   php -r "var_dump(opcache_get_status());"
   # Check that validate_timestamps is enabled
   ```

4. **Clear browser cache:**
   - Hard refresh: `Ctrl + Shift + R` (Windows/Linux) or `Cmd + Shift + R` (Mac)
   - Or use incognito/private browsing mode

5. **Check for syntax errors:**
   - PHP syntax errors can prevent files from loading
   - Check Render logs for PHP errors

### Docker Layer Caching Issues

If Docker is using cached layers:

1. **Force rebuild without cache:**
   - Render Dashboard → Manual Deploy → "Clear build cache & deploy"

2. **Add a build argument to bust cache:**
   - Add `ARG CACHE_BUST=1` to Dockerfile
   - Or modify a file that's copied early in the Dockerfile

3. **Use .dockerignore properly:**
   - Ensure cache files aren't being copied
   - Check that `storage/`, `bootstrap/cache/` are in `.dockerignore`

## Environment Variables

Add these to Render Dashboard if needed:

- `ALLOW_CACHE_CLEAR_ROUTE=true` - Enable `/dev/clear-cache` route in production
- `ASSET_VERSION=<timestamp>` - Force asset cache refresh (optional)

## Best Practices

1. **Always test locally first** - Ensure changes work locally before deploying
2. **Use version control** - Commit and push all changes before deploying
3. **Monitor deployments** - Watch Render logs during deployment
4. **Clear caches after deployment** - Use the cache clearing route or restart service
5. **Use staging environment** - Test changes in staging before production

## Still Having Issues?

1. **Check Render Status Page**: [status.render.com](https://status.render.com)
2. **Review Application Logs**: Render Dashboard → Logs
3. **Contact Render Support**: If the issue persists
4. **Verify Git Repository**: Ensure all changes are committed and pushed

