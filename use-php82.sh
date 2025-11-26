#!/bin/bash
# Quick script to use PHP 8.2 in Git Bash
# Run this in Git Bash: source use-php82.sh

echo "Setting PHP 8.2.29 in PATH..."

# Remove existing PHP paths and add PHP 8.2.29
export PATH="/c/laragon/bin/php/php-8.2.29-nts-Win32-vs16-x64:$(echo $PATH | tr ':' '\n' | grep -v 'laragon/bin/php' | tr '\n' ':' | sed 's/:$//')"

echo "✓ PHP version:"
php -v
echo ""
echo "You can now run: php artisan serve"









