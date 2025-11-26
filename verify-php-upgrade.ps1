# PHP Version Verification Script
Write-Host "=== PHP Version Check ===" -ForegroundColor Cyan
Write-Host ""

# Check current PHP version
Write-Host "Current PHP version:" -ForegroundColor Yellow
php -v
Write-Host ""

# Check if PHP 8.2+ is available
Write-Host "Checking for PHP 8.2+ installations in Laragon..." -ForegroundColor Yellow
if (Test-Path "C:\laragon\bin\php") {
    $phpVersions = Get-ChildItem "C:\laragon\bin\php" -Directory | Where-Object { $_.Name -match "php-8\.[23]" }
    if ($phpVersions) {
        Write-Host "✓ Found PHP 8.2+ versions:" -ForegroundColor Green
        $phpVersions | ForEach-Object { Write-Host "  - $($_.Name)" -ForegroundColor Green }
    } else {
        Write-Host "✗ No PHP 8.2+ found. Please install PHP 8.2 or 8.3" -ForegroundColor Red
    }
} else {
    Write-Host "✗ Laragon PHP directory not found" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== Next Steps ===" -ForegroundColor Cyan
Write-Host "1. If PHP 8.2+ is installed, switch to it in Laragon:" -ForegroundColor Yellow
Write-Host "   Right-click Laragon icon → PHP → Version → Select php-8.2.x" -ForegroundColor White
Write-Host ""
Write-Host "2. Close this terminal and open a new one" -ForegroundColor Yellow
Write-Host ""
Write-Host "3. Run: php -v (should show 8.2+)" -ForegroundColor Yellow
Write-Host ""
Write-Host "4. Then run: composer install" -ForegroundColor Yellow










