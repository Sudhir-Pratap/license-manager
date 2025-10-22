@echo off
echo 🎯 Testing Acecoderz License Manager Package Installation (Local)
echo ==================================================

echo 📦 Creating test Laravel project...
composer create-project laravel/laravel test-install --prefer-dist --no-interaction
if %errorlevel% neq 0 goto error

cd test-install

echo 🔧 Adding local package repository...
composer config repositories.local '{"type": "path", "url": "../", "options": {"symlink": true}}' --file composer.json
if %errorlevel% neq 0 goto error

echo 📥 Installing license-manager package from local path...
composer require acecoderz/license-manager:@dev --no-interaction
if %errorlevel% neq 0 goto error

echo ⚙️ Publishing configuration...
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider" --tag=config --no-interaction
if %errorlevel% neq 0 goto error

echo 🧪 Testing package commands...
php artisan list | findstr license
if %errorlevel% neq 0 goto error

echo.
echo ✅ Installation test completed successfully!
echo 📁 Configuration file: test-install/config/license-manager.php
echo 🔍 Test project location: test-install/
echo.
echo 💡 To keep test project, press Ctrl+C now. Otherwise cleaning up in 5 seconds...
timeout /t 5

cd ..
rmdir /s /q test-install

echo.
echo 🎉 Package installation test successful!
echo 📦 Package is ready for distribution!
goto end

:error
echo.
echo ❌ Installation failed! Check errors above.
cd ..
exit /b 1

:end
