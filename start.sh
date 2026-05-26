#!/usr/bin/env bash
set -e

# ─── Learn Academy Platform — quick start ─────────────────────────────────────
# Installs dependencies and starts the local development server.
# Usage: ./start.sh [port]

PORT="${1:-8080}"

# Check PHP
if ! command -v php &>/dev/null; then
    echo "ERROR: PHP not found. Install PHP 8.1+ and try again."
    exit 1
fi

PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
REQUIRED="8.1"
if ! php -r "exit(version_compare(PHP_VERSION, '$REQUIRED', '>=') ? 0 : 1);"; then
    echo "ERROR: PHP $REQUIRED+ required (found $PHP_VER)."
    exit 1
fi

# Check composer
if ! command -v composer &>/dev/null; then
    echo "ERROR: Composer not found."
    echo "Install it from https://getcomposer.org or run:"
    echo "  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer"
    exit 1
fi

# Install dependencies
echo "→ Installing dependencies..."
composer install --no-interaction --prefer-dist --quiet

# First-time config
if [ ! -f app/config.local.php ]; then
    cp app/config.php app/config.local.php
    echo "→ Created app/config.local.php — edit it to add Stripe/PayPal keys."
fi

echo ""
echo "→ Starting server at http://localhost:$PORT"
echo "   Press Ctrl+C to stop."
echo ""

PORT="$PORT" make dev
