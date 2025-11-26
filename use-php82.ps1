# Quick script to use PHP 8.2 in current terminal session
# Run this in PowerShell: . .\use-php82.ps1

Write-Host "Setting PHP 8.2.29 in PATH..." -ForegroundColor Cyan

# Remove all PHP paths from PATH
$newPath = ($env:Path -split ';' | Where-Object { $_ -notmatch 'laragon\\bin\\php' }) -join ';'

# Add PHP 8.2.29 first
$php82Path = "C:\laragon\bin\php\php-8.2.29-nts-Win32-vs16-x64"
$env:Path = "$php82Path;$newPath"

Write-Host "✓ PHP version:" -ForegroundColor Green
php -v
Write-Host ""
Write-Host "You can now run: php artisan serve" -ForegroundColor Yellow









