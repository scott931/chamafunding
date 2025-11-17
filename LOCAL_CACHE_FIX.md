# Local Changes Not Reflecting - Quick Fix Guide

## Problem
Your local changes are not showing up locally but are working online. This is typically caused by:
1. **OPCache** (PHP opcode cache) - Most common cause
2. **Laravel caches** (config, routes, views)
3. **Browser cache**
4. **Web server needs restart**

## Quick Fix (Do These Steps)

### Step 1: Clear All Caches
Run the PowerShell script:
```powershell
.\clear-cache.ps1
```

Or manually:
```powershell
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 2: Clear OPCache via Browser
Since OPCache is enabled and CLI OPCache is usually disabled, visit this URL in your browser:
```
http://localhost/dev/clear-cache
```
(Replace `localhost` with your local domain if different)

This will:
- Clear all Laravel caches
- Clear OPCache (if accessible)
- Show you the cache status

### Step 3: Restart Your Web Server
**This is the most important step!** OPCache is tied to your web server process.

**If using PHP built-in server:**
- Stop the server (Ctrl+C)
- Restart: `php artisan serve`

**If using Apache:**
- Restart Apache service
- Or: `net stop Apache2.4` then `net start Apache2.4`

**If using Nginx + PHP-FPM:**
- Restart PHP-FPM service
- Or restart both Nginx and PHP-FPM

**If using XAMPP/WAMP:**
- Restart Apache from the control panel

### Step 4: Hard Refresh Browser
- **Windows/Linux**: `Ctrl + Shift + R` or `Ctrl + F5`
- **Mac**: `Cmd + Shift + R`

Or:
1. Open Developer Tools (F12)
2. Right-click the refresh button
3. Select "Empty Cache and Hard Reload"

### Step 5: Verify Changes
After completing the above steps, your changes should now be visible.

## Why This Happens

1. **OPCache** caches compiled PHP code in memory for performance
2. When you edit files, OPCache may still serve the old cached version
3. Online servers typically clear OPCache on deployment, which is why changes work there
4. Locally, OPCache persists until you restart the web server or clear it

## Prevention Tips

1. **Disable OPCache in development** (optional):
   Edit your `php.ini`:
   ```ini
   opcache.enable=0
   ```
   Then restart your web server.

2. **Use the cache clearing route**:
   Bookmark `http://localhost/dev/clear-cache` and visit it after making changes.

3. **Keep DevTools open with cache disabled**:
   - Open DevTools (F12)
   - Go to Network tab
   - Check "Disable cache"
   - Keep DevTools open while developing

4. **Use the PowerShell script**:
   Run `.\clear-cache.ps1` regularly during development.

## Still Not Working?

1. **Check file permissions** - Make sure files are writable
2. **Verify you're editing the right files** - Check file paths
3. **Check for syntax errors** - PHP errors can prevent files from loading
4. **Verify APP_ENV=local** in your `.env` file
5. **Check if you're using a different PHP version** - `php -v`
6. **Try incognito/private browsing mode** - Rules out browser cache issues

## Development Route

The `/dev/clear-cache` route is only available when `APP_ENV=local` for security. It provides:
- JSON response with cache clearing status
- OPCache status information
- Timestamp of when caches were cleared

