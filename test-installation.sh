#!/bin/bash
set -e  # Exit on error

echo "🎯 Testing Acecoderz License Manager Package Installation (Local)"
echo "=================================================="

# Cleanup function
cleanup() {
    echo ""
    echo "🧹 Cleaning up test installation..."
    cd ..
    rm -rf test-install
}

# Trap errors
trap 'echo "❌ Installation failed! Check errors above."; cleanup; exit 1' ERR

echo "📦 Creating test Laravel project..."
composer create-project laravel/laravel test-install --prefer-dist --no-interaction

cd test-install

echo "🔧 Adding local package repository..."
composer config repositories.local '{"type": "path", "url": "../", "options": {"symlink": true}}' --file composer.json

echo "📥 Installing license-manager package from local path..."
composer require insurance-core/helpers:@dev --no-interaction

echo "⚙️ Publishing configuration..."
php artisan vendor:publish --provider="InsuranceCore\\Helpers\\HelperServiceProvider" --tag=config --no-interaction

echo "🧪 Testing package commands..."
php artisan list | grep license

echo ""
echo "✅ Installation test completed successfully!"
echo "📁 Configuration file: test-install/config/license-manager.php"
echo "🔍 Test project location: test-install/"
echo ""
echo "💡 To keep test project, press Ctrl+C now. Otherwise cleaning up in 5 seconds..."
sleep 5

cleanup

echo ""
echo "🎉 Package installation test successful!"
echo "📦 Package is ready for distribution!"
