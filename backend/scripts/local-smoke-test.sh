#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "=================================================="
echo "🚀 Running Local Smoke Tests for Ecommerce Boilerplate"
echo "=================================================="

# 1. Verify environment file
if [ ! -f .env ]; then
    echo "⚠️  .env file not found! Copying from .env.example..."
    cp .env.example .env
fi

# 2. Check PHP configuration and version
PHP_VER=$(php -r "echo PHP_VERSION;")
echo "✅ PHP Version: $PHP_VER"

# 3. Check Composer dependencies
if [ ! -d vendor ]; then
    echo "⚠️  vendor directory not found! Running composer install..."
    composer install --no-interaction --prefer-dist
else
    echo "✅ Composer dependencies are present."
fi

# 4. Check Node and NPM dependencies
if [ ! -d node_modules ]; then
    echo "⚠️  node_modules not found! Running npm install..."
    npm install
else
    echo "✅ NPM dependencies are present."
fi

# 5. Clear Configuration Cache to avoid stale values
echo "🧹 Clearing configuration cache..."
php artisan config:clear --quiet

# 6. Verify SQLite database exists (if sqlite is used)
DB_CONN=$(php artisan tinker --execute="echo config('database.default');" | tr -d '\r\n')
echo "📦 Database connection: $DB_CONN"

if [ "$DB_CONN" = "sqlite" ]; then
    DB_FILE=$(php artisan tinker --execute="echo config('database.connections.sqlite.database');" | tr -d '\r\n')
    if [ ! -f "$DB_FILE" ] && [ "$DB_FILE" = "database/database.sqlite" ]; then
        echo "⚠️  SQLite database file missing! Creating database/database.sqlite..."
        touch database/database.sqlite
    fi
fi

# 7. Check if migrations are up-to-date
echo "⚙️  Verifying database migrations..."
php artisan migrate --force

# 8. Run Automated Tests
echo "🧪 Running tests..."
php artisan test

echo "=================================================="
echo "🎉 ALL SMOKE TESTS PASSED SUCCESSFULLY! Ready to dev! 🚀"
echo "=================================================="
