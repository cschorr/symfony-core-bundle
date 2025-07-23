#!/usr/bin/env bash

echo "🔍 Running initial PHPCS check..."
./vendor/bin/phpcs --warning-severity=6 --basepath=src

echo ""
echo "🔧 Running PHPCBF to fix coding standards..."
./vendor/bin/phpcbf src

echo ""
echo "⚡ Running Rector to modernize code..."
./vendor/bin/rector process src

echo ""
echo "✨ Running PHP-CS-Fixer for final formatting..."
./vendor/bin/php-cs-fixer fix src

echo ""
echo "✅ Running final PHPCS check..."
./vendor/bin/phpcs --warning-severity=6 --basepath=src

echo ""
echo "🎉 Code beautification complete!"
