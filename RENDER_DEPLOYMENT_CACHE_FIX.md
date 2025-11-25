# Render Deployment Cache Fix - Changes Not Appearing Online

## Problem
Changes are committed and pushed, but old code is still showing on Render. This is caused by **Docker layer caching** - Render is using cached Docker layers from previous builds.

## Quick Fix (Recommended)

### Option 1: Clear Build Cache in Render Dashboard
1. Go to [Render Dashboard](https://dashboard.render.com)
2. Navigate to your `chamafunding-web` service
3. Click **"Manual Deploy"** → **"Clear build cache & deploy"**
4. This forces a complete rebuild without using cached Docker layers
5. Wait for deployment to complete (usually 5-10 minutes)

### Option 2: Use Render CLI
```bash
render services:deploy chamafunding-web --clear-cache
```

### Option 3: Force Rebuild via Git
Add an empty commit to trigger a fresh build:
```bash
git commit --allow-empty -m "Force rebuild - clear cache"
git push
```
Then in Render Dashboard, manually deploy with "Clear build cache & deploy"

## What Was Fixed

### 1. Dockerfile Cache Busting
- Added build ID generation that changes on every build
- This ensures the `COPY . /var/www/html` layer is rebuilt on each deployment
- Build information is logged during Docker build for verification

### 2. Enhanced Cache Clearing
- `start.sh` script clears all caches on every container start
- OPCache is reset and invalidated on startup
- All Laravel caches (config, routes, views) are cleared

### 3. Build Verification
- Dockerfile now logs build information during build
- Shows file counts and timestamps to verify fresh files are copied

## Why This Happens

1. **Docker Layer Caching**: Docker caches layers to speed up builds
2. **Render Build Cache**: Render caches Docker layers between deployments
3. **OPCache**: PHP opcode cache may serve old compiled code
4. **Laravel Caches**: Config, routes, and views may be cached

## Prevention Strategies

### Always Clear Build Cache After Code Changes
When you make code changes:
1. **Commit and push** your changes
2. **Use "Clear build cache & deploy"** in Render Dashboard
3. **Monitor deployment logs** to ensure fresh build

### Check Deployment Logs
After deployment, check logs for:
- `=== Build Information ===` - Shows build timestamp
- `Files copied at:` - Confirms when files were copied
- `PHP files count:` - Verifies files are present
- `Clearing all caches...` - Confirms cache clearing

### Verify Changes Are Deployed
1. **Check build logs** in Render Dashboard
2. **Look for build timestamp** - Should be recent
3. **Check file counts** - Should match your codebase
4. **Visit cache clear route** (if enabled): `https://chamafunding-web.onrender.com/dev/clear-cache`

## Troubleshooting

### Changes Still Not Appearing?

1. **Verify deployment completed:**
   - Check Render Dashboard → Deployments
   - Ensure latest deployment shows "Live" status
   - Check for any build errors

2. **Verify you cleared build cache:**
   - Look for "Clearing build cache" in build logs
   - Check build timestamp in logs (should be recent)

3. **Check if files were actually updated:**
   - Use Render Shell: `render shell chamafunding-web`
   - Check file content: `cat /var/www/html/app/Http/Controllers/YourController.php`
   - Verify it matches your latest code

4. **Clear caches after deployment:**
   - Visit: `https://chamafunding-web.onrender.com/dev/clear-cache`
   - Or restart the service in Render Dashboard

5. **Check browser cache:**
   - Hard refresh: `Ctrl + Shift + R` (Windows/Linux) or `Cmd + Shift + R` (Mac)
   - Or use incognito/private browsing mode

6. **Verify OPCache settings:**
   - OPCache should have `validate_timestamps=1` and `revalidate_freq=0`
   - This is configured in Dockerfile

## Best Practices

1. **Always use "Clear build cache & deploy"** when:
   - Making code changes
   - Updating dependencies
   - Changing configuration files
   - After merging pull requests

2. **Monitor deployment logs** to catch issues early

3. **Test locally first** before deploying

4. **Use version control** - commit and push all changes

5. **Keep deployment logs** - They help diagnose cache issues

## Environment Variables

Make sure these are set in Render Dashboard:
- `ALLOW_CACHE_CLEAR_ROUTE=true` - Enables `/dev/clear-cache` route
- `APP_ENV=production` - Ensures production settings
- `APP_DEBUG=false` - Disables debug mode

## Still Having Issues?

1. **Check Render Status**: [status.render.com](https://status.render.com)
2. **Review Application Logs**: Render Dashboard → Logs
3. **Contact Render Support**: If issue persists
4. **Verify Git Repository**: Ensure all changes are committed and pushed

## Quick Reference

```bash
# Force rebuild (Render CLI)
render services:deploy chamafunding-web --clear-cache

# Check service status
render services:list

# View logs
render logs chamafunding-web

# Open shell
render shell chamafunding-web
```

