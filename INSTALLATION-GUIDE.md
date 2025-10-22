# 📦 License Manager - Installation Guide

## Prerequisites

- PHP ^8.1, ^8.2, or ^8.3
- Laravel ^9.0, ^10.0, ^11.0, or ^12.0
- Composer

---

## Installation Methods

### Method 1: Local Development (Recommended for Development)

If you're developing both packages locally:

#### Step 1: Add Repository to Server Project

Navigate to your `license-server` project and edit `composer.json`:

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

#### Step 2: Require the Package

```bash
cd /path/to/license-server
composer require acecoderz/license-manager:@dev
```

The `@dev` suffix tells Composer to use the local development version.

#### Step 3: Publish Configuration

```bash
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider"
```

This will create `config/license-manager.php` in your Laravel project.

---

### Method 2: Git Repository

If the package is in a Git repository:

#### Step 1: Add VCS Repository

In your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/acecoderz/laravel-license-manager"
        }
    ]
}
```

#### Step 2: Require the Package

```bash
composer require acecoderz/license-manager:dev-master
# or for a specific version
composer require acecoderz/license-manager:^1.0
```

#### Step 3: Publish Configuration

```bash
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider"
```

---

### Method 3: Private Packagist (Production)

For production deployments, publish to a private Packagist or Satis repository.

---

## Configuration

### Step 1: Environment Variables

Add to your `.env` file:

```env
# License Configuration
LICENSE_KEY=your_license_key_here
LICENSE_PRODUCT_ID=your_product_id
LICENSE_CLIENT_ID=your_client_id
LICENSE_SERVER=https://license.acecoderz.com
LICENSE_API_TOKEN=your_api_token

# Optional: Security
LICENSE_SECRET=your_secret_key
LICENSE_SECURITY_HASH=your_security_hash
LICENSE_BYPASS_TOKEN=your_bypass_token

# Optional: Stealth Mode (Recommended for Production)
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
LICENSE_BACKGROUND_VALIDATION=true
LICENSE_SILENT_FAIL=true

# Optional: Deployment Settings
LICENSE_BIND_DOMAIN_ONLY=false
LICENSE_CANONICAL_DOMAIN=yourdomain.com
LICENSE_FORCE_REGENERATE_FINGERPRINT=false

# Optional: Cache Duration (in minutes)
LICENSE_CACHE_DURATION=1440
```

### Step 2: Review Configuration File

Check `config/license-manager.php` and adjust settings as needed.

---

## Middleware Setup

### Automatic Middleware (Recommended)

Set in `.env`:
```env
LICENSE_AUTO_MIDDLEWARE=true
```

This automatically applies license protection to all web routes.

### Manual Middleware

Apply to specific routes:

```php
// In routes/web.php or routes/api.php

// Protect specific routes
Route::middleware(['license'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::resource('/users', UserController::class);
});

// Or use anti-piracy middleware
Route::middleware(['anti-piracy'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// Or use stealth mode
Route::middleware(['stealth-license'])->group(function () {
    Route::get('/app', [AppController::class, 'index']);
});
```

### Global Middleware

In `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware::class,
    ],
];
```

---

## Available Artisan Commands

```bash
# Check license status (client-friendly)
php artisan license:client-status --check

# Configure stealth installation
php artisan license:stealth-install --config

# Diagnose deployment issues
php artisan license:deployment-license

# View license information
php artisan license:info

# Generate new license (requires server access)
php artisan license:generate

# Reset license cache
php artisan license:cache-reset

# Diagnose license issues
php artisan license:diagnose

# Test anti-piracy features
php artisan license:test-antipiracy

# Copy protection commands
php artisan license:copy-protection
```

---

## Verification

### Test Installation

1. **Check if package is loaded:**
```bash
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider"
```

2. **List license commands:**
```bash
php artisan list | grep license
```

Expected output:
```
license
  license:cache-reset         Reset license validation cache
  license:client-friendly     Client-friendly license status and diagnostics
  license:client-status       Client-friendly license status and diagnostics
  license:copy-protection     Enable advanced copy protection features
  license:deployment-license  Deployment-safe license management
  license:diagnose            Diagnose license validation issues
  license:generate            Generate a new license key
  license:info                Display license information
  license:stealth-install     Configure stealth license installation
  license:test-antipiracy     Test anti-piracy protection features
```

3. **Check client status:**
```bash
php artisan license:client-status --check
```

---

## Troubleshooting

### Issue: Package not found

**Solution:** Ensure the repository path is correct in `composer.json`

```bash
# Check composer repositories
composer config repositories
```

### Issue: Service provider not loaded

**Solution:** Clear Laravel cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
composer dump-autoload
```

### Issue: Middleware not working

**Solution:** 
1. Check if middleware is registered:
```bash
php artisan route:list
```

2. Verify configuration:
```bash
php artisan config:cache
```

### Issue: License validation fails

**Solution:** Run diagnostics
```bash
php artisan license:diagnose
php artisan license:deployment-license
```

---

## Development vs Production

### Development Setup
- Use `@dev` version
- Enable verbose logging
- Disable stealth mode
- Use `LICENSE_BIND_DOMAIN_ONLY=false`

### Production Setup
- Use tagged version (e.g., `^1.0`)
- Enable stealth mode
- Mute client-visible logs
- Set proper `LICENSE_CANONICAL_DOMAIN`
- Enable `LICENSE_STEALTH_MODE=true`

---

## Security Considerations

1. **Never commit `.env` file** - Keep license keys secure
2. **Use environment-specific keys** - Different keys for dev/staging/production
3. **Enable stealth mode in production** - Hide license checks from end users
4. **Monitor license server logs** - Track validation attempts
5. **Rotate API tokens regularly** - Change `LICENSE_API_TOKEN` periodically

---

## Next Steps

1. ✅ Install package
2. ✅ Publish configuration
3. ✅ Set environment variables
4. ✅ Apply middleware
5. ✅ Test with `php artisan license:client-status`
6. ✅ Deploy to production

---

## Support

- Email: support@acecoderz.com
- Documentation: https://docs.acecoderz.com
- Issues: https://github.com/acecoderz/laravel-license-manager/issues

