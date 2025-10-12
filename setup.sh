#!/bin/bash

# Set temporary HOME for Composer
export HOME=$(pwd)/.composer_home
mkdir -p "$HOME"

# Automatically detect PHP binary path
PHP_BIN="$(which php)"

# Step 1: Download Composer installer
echo "Downloading Composer installer..."
$PHP_BIN -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Step 2: Install Composer
echo "Installing Composer..."
$PHP_BIN composer-setup.php --install-dir=. --filename=composer.phar

# Step 3: Run Composer install
echo "Running composer install with --no-dev and --optimize-autoloader..."
$PHP_BIN composer.phar install --no-dev --optimize-autoloader

# Step 4: Clean up
echo "Cleaning up installer files..."
rm -f composer-setup.php
rm -rf "$HOME"

echo "Done."
