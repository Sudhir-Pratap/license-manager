# 🚀 License Manager - Quick Start Guide

Get up and running with the License Manager in 5 minutes!

---

## Step 1: Install Package

```bash
composer require acecoderz/license-manager
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider"
php artisan package:discover --ansi
```

---

## Step 2: Configure .env

Add these lines to your `.env` file:

```env
LICENSE_SERVER=https://license.acecoderz.com
LICENSE_API_TOKEN=xDQhdSFQkmk2xds1L2jsu9Ill4c7kbkeoj3+JWy7ng0=
LICENSE_KEY=your_generated_license_key
LICENSE_PRODUCT_ID=YOUR_PRODUCT_ID
LICENSE_CLIENT_ID=YOUR_CLIENT_ID

# Stealth Mode (recommended)
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
```

---

## Step 3: Protect Your Routes

### Option A: All Routes (Recommended for API)

```php
// routes/api.php
Route::middleware('license')->group(function () {
    // All your protected routes
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

### Option B: Specific Routes

```php
// routes/web.php
Route::get('/admin', [AdminController::class, 'index'])->middleware('license');
```

### Option C: Controller Level

```php
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('license');
    }
}
```

---

## Step 4: Test It

```bash
php artisan license:client-status --check
```

**Expected Result:**
```
🎉 Overall Status: HEALTHY
Your system is operating normally with proper license validation.
```

---

## Step 5: Verify Installation

```bash
php artisan license:info
```

This shows your hardware fingerprint and installation ID.

---

## ✅ Done!

Your license protection is now active. The package will:
- ✅ Validate licenses automatically
- ✅ Work transparently for users
- ✅ Handle deployments gracefully
- ✅ Protect against piracy

---

## 🆘 Need Help?

Run diagnostics:
```bash
php artisan license:diagnose
```

Check status:
```bash
php artisan license:client-status --check
```

---

## 📚 More Information

- **Full Guide:** See `USAGE-GUIDE.md`
- **Configuration:** See `config/license-manager.php`
- **Commands:** Run `php artisan list license`

