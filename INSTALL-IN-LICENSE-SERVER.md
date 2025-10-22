# 🚀 Quick Installation in License-Server Project

## Step-by-Step Guide

### Step 1: Navigate to license-server project

```bash
cd "D:\Laravel Projects\Packages\license-server"
```

### Step 2: Add the local package repository

Add this to `composer.json` (in the license-server project):

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../license-manager",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

Or use the command line:

```bash
composer config repositories.license-manager '{"type": "path", "url": "../license-manager", "options": {"symlink": true}}'
```

### Step 3: Require the package

```bash
composer require acecoderz/license-manager:@dev
```

### Step 4: Publish the configuration

```bash
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider" --tag=config
```

### Step 5: Verify installation

```bash
# List license commands
php artisan list | findstr license

# Check if config file exists
dir config\license-manager.php
```

### Step 6: Configure environment

Add to `.env` file in license-server:

```env
# License Configuration
LICENSE_KEY=test-license-key
LICENSE_PRODUCT_ID=test-product-id
LICENSE_CLIENT_ID=test-client-id
LICENSE_SERVER=http://localhost:8000
LICENSE_API_TOKEN=test-api-token

# Stealth Mode
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
LICENSE_SILENT_FAIL=true
```

### Step 7: Test the package

```bash
# Check license status
php artisan license:client-status --check

# View license info
php artisan license:info

# Run diagnostics
php artisan license:diagnose
```

---

## Troubleshooting

### If composer require fails:

```bash
# Clear composer cache
composer clear-cache

# Dump autoload
composer dump-autoload

# Try again
composer require acecoderz/license-manager:@dev --prefer-source
```

### If commands don't appear:

```bash
# Clear Laravel cache
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

# Dump autoload
composer dump-autoload

# Check again
php artisan list
```

### If you get "Class not found" errors:

```bash
# Regenerate autoload files
composer dump-autoload -o

# Clear all caches
php artisan optimize:clear
```

---

## Uninstalling (if needed)

```bash
# Remove the package
composer remove acecoderz/license-manager

# Remove repository config
composer config --unset repositories.license-manager

# Delete config file
del config\license-manager.php
```

---

## All Issues Fixed ✅

The following issues have been resolved:

1. ✅ **Laravel 12 compatibility** - Added support for Laravel 12
2. ✅ **Missing dependencies** - Added illuminate/filesystem and illuminate/database
3. ✅ **Carbon dependency** - Removed (already included with Laravel)
4. ✅ **Symfony 7 compatibility** - Added support for Symfony 7
5. ✅ **LicenseManager registration** - Now properly registered in service provider
6. ✅ **Local installation** - Updated test scripts and documentation

The package is now ready to install! 🎉

