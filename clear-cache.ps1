# PowerShell script to clear all caches for local development
# Run this script when your changes aren't reflecting locally

Write-Host "Clearing all caches..." -ForegroundColor Cyan

# Change to project directory
Set-Location $PSScriptRoot

# Clear Laravel caches
Write-Host "`n1. Clearing Laravel caches..." -ForegroundColor Yellow
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear compiled views
Write-Host "`n2. Removing compiled views..." -ForegroundColor Yellow
if (Test-Path "storage\framework\views") {
    Remove-Item "storage\framework\views\*.php" -Force -ErrorAction SilentlyContinue
}

# Clear bootstrap cache
Write-Host "`n3. Clearing bootstrap cache..." -ForegroundColor Yellow
if (Test-Path "bootstrap\cache") {
    Get-ChildItem "bootstrap\cache\*.php" | Remove-Item -Force -ErrorAction SilentlyContinue
}

# Attempt to clear OPCache (note: CLI OPCache is usually disabled)
Write-Host "`n4. Attempting to clear OPCache..." -ForegroundColor Yellow
php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPCache cleared via CLI\n'; } else { echo 'OPCache not available via CLI (restart your web server)\n'; }"

Write-Host ""
Write-Host "Cache clearing complete!" -ForegroundColor Green
Write-Host ""
Write-Host "IMPORTANT: If changes still don't appear:" -ForegroundColor Red
Write-Host "  1. Restart your web server (Apache/Nginx/PHP built-in server)" -ForegroundColor Yellow
Write-Host "  2. Visit http://localhost/dev/clear-cache in your browser to clear OPCache" -ForegroundColor Yellow
Write-Host "  3. Hard refresh your browser (Ctrl + Shift + R)" -ForegroundColor Yellow
Write-Host "  4. Clear browser cache or use Incognito mode" -ForegroundColor Yellow

