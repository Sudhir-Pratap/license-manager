# 📘 License Manager Package - Complete Usage Guide

## 🎯 Overview

The **Acecoderz License Manager** is an enterprise-grade license validation package for Laravel applications. It provides advanced license protection, anti-piracy detection, and seamless integration with your Laravel project.

---

## 📦 Installation

### Step 1: Install via Composer

```bash
composer require acecoderz/license-manager
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider"
```

This creates `config/license-manager.php` in your project.

### Step 3: Run Package Discovery

```bash
php artisan package:discover --ansi
```

---

## ⚙️ Configuration

### Basic Configuration (.env)

Add these variables to your `.env` file:

```env
# License Server URL
LICENSE_SERVER=https://license.acecoderz.com

# API Authentication Token
LICENSE_API_TOKEN=xDQhdSFQkmk2xds1L2jsu9Ill4c7kbkeoj3+JWy7ng0=

# License Details
LICENSE_KEY=your_generated_license_key
LICENSE_PRODUCT_ID=YOUR_PRODUCT_ID
LICENSE_CLIENT_ID=YOUR_CLIENT_ID

# Cache Duration (in minutes, default: 1440 = 24 hours)
LICENSE_CACHE_DURATION=1440

# Stealth Mode (recommended for production)
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
```

### Advanced Configuration

Edit `config/license-manager.php` for advanced settings:

```php
return [
    // Basic Configuration
    'license_key' => env('LICENSE_KEY'),
    'product_id' => env('LICENSE_PRODUCT_ID'),
    'client_id' => env('LICENSE_CLIENT_ID'),
    'license_server' => env('LICENSE_SERVER', 'https://license.acecoderz.com'),
    'api_token' => env('LICENSE_API_TOKEN'),
    
    // Stealth Mode Configuration
    'stealth' => [
        'enabled' => env('LICENSE_STEALTH_MODE', true),
        'hide_ui_elements' => env('LICENSE_HIDE_UI', true),
        'mute_logs' => env('LICENSE_MUTE_LOGS', true),
        'background_validation' => env('LICENSE_BACKGROUND_VALIDATION', true),
        'validation_timeout' => env('LICENSE_VALIDATION_TIMEOUT', 5),
        'fallback_grace_period' => env('LICENSE_GRACE_PERIOD', 72),
        'silent_fail' => env('LICENSE_SILENT_FAIL', true),
    ],
    
    // Anti-Piracy Configuration
    'anti_reselling' => [
        'threshold_score' => env('LICENSE_RESELL_THRESHOLD', 75),
        'max_domains' => env('LICENSE_MAX_DOMAINS', 2),
        'max_per_geo' => env('LICENSE_MAX_PER_GEO', 3),
        'detect_vpn' => env('LICENSE_DETECT_VPN', true),
        'monitor_patterns' => env('LICENSE_MONITOR_PATTERNS', true),
        'file_integrity' => env('LICENSE_FILE_INTEGRITY', true),
    ],
];
```

---

## 🚀 Quick Start Guide

### Step 1: Get Your License Key

Contact the license server administrator or use the license generation command:

```bash
php artisan license:generate \
    --product-id=YOUR_PRODUCT_ID \
    --domain=yourdomain.com \
    --ip=YOUR_SERVER_IP \
    --client-id=YOUR_CLIENT_ID
```

### Step 2: Install License Key

```bash
php artisan license:stealth-install
```

This command will:
- Store the license key securely
- Configure stealth mode
- Test license validation
- Set up hardware fingerprint

### Step 3: Verify Installation

```bash
php artisan license:client-status --check
```

Expected output:
```
🎉 Overall Status: HEALTHY
Your system is operating normally with proper license validation.
```

---

## 🔧 Usage Examples

### 1. Basic License Validation

Use the middleware to protect your routes:

```php
// In routes/web.php or routes/api.php
Route::middleware(['license'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports', [ReportsController::class, 'index']);
});
```

### 2. Programmatic Validation

Check license status in your controllers:

```php
use Acecoderz\LicenseManager\LicenseManager;

class DashboardController extends Controller
{
    public function index(LicenseManager $licenseManager)
    {
        // Validate license
        $isValid = $licenseManager->validateLicense(
            config('license-manager.license_key'),
            config('license-manager.product_id'),
            request()->getHost(),
            request()->ip(),
            config('license-manager.client_id')
        );
        
        if (!$isValid) {
            return response('License validation failed', 403);
        }
        
        return view('dashboard');
    }
}
```

### 3. Check License Status

Use the client-friendly command:

```bash
php artisan license:client-status --check
```

### 4. Advanced: Custom License Check

```php
use Acecoderz\LicenseManager\LicenseManager;

$licenseManager = app(LicenseManager::class);

// Get hardware fingerprint
$fingerprint = $licenseManager->generateHardwareFingerprint();

// Get installation ID
$installationId = $licenseManager->getOrCreateInstallationId();

// Get installation details
$details = $licenseManager->getInstallationDetails();
```

---

## 🛡️ Middleware Usage

### Option 1: Route Middleware

```php
// In routes/web.php
Route::middleware('license')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/settings', [SettingsController::class, 'index']);
});
```

### Option 2: Controller Middleware

```php
use Acecoderz\LicenseManager\Http\Middleware\LicenseSecurity;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(LicenseSecurity::class);
    }
    
    public function index()
    {
        return view('admin.dashboard');
    }
}
```

### Option 3: Global Middleware (Not Recommended)

```php
// In bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    if (config('license-manager.auto_middleware')) {
        $middleware->alias([
            'license' => LicenseSecurity::class,
        ]);
    }
});
```

---

## 🎨 Available Commands

### 1. License Status Check

**Purpose:** Check your license status (client-friendly)

```bash
php artisan license:client-status --check
```

**Output:**
```
=== System Status Check ===

License Configuration:
License Key: ✅ Configured
Product ID: YOUR_PRODUCT_ID
Client ID: YOUR_CLIENT_ID
License Server: https://license.acecoderz.com

Operation Mode: Silent (Hidden)

Domain Usage:
Total Domains: 1/2 (allowed)
  - yourdomain.com

Security Status:
✅ Normal operation - No suspicious activity detected

🎉 Overall Status: HEALTHY
Your system is operating normally with proper license validation.
```

### 2. License Information

**Purpose:** Show hardware fingerprint and installation ID

```bash
php artisan license:info
```

**Output:**
```
License Information:

Hardware Fingerprint: d3087628e3bf6e4c1d667de5e1fd1d9e...
Installation ID: f47c5505-89d8-4572-be4d-6f70b37b21ad
Current IP: 127.0.0.1

Current Configuration:
License Key: Configured
Product ID: YOUR_PRODUCT_ID
Client ID: YOUR_CLIENT_ID
```

### 3. Stealth Installation

**Purpose:** Configure stealth mode for production

```bash
php artisan license:stealth-install
```

This command will:
- Test license validation
- Configure stealth mode
- Verify hardware fingerprint
- Set up automatic background validation

### 4. Reset Cache

**Purpose:** Clear license cache (for troubleshooting)

```bash
php artisan license:reset-cache
```

Use this if you encounter:
- License validation errors
- Hardware fingerprint issues
- Installation ID problems

### 5. Diagnose Issues

**Purpose:** Diagnose license validation problems

```bash
php artisan license:diagnose
```

This will check:
- License configuration
- Hardware fingerprint
- Cache status
- Server connectivity
- Anti-piracy status

### 6. Test Anti-Piracy

**Purpose:** Test the anti-piracy system

```bash
php artisan license:test-anti-piracy
```

This will:
- Check for suspicious activity
- Test violation detection
- Show security scores
- Generate detailed report

### 7. Deployment License Check

**Purpose:** Help with license issues during deployment

```bash
php artisan license:deployment
```

This will:
- Check deployment environment
- Verify domain/IP changes
- Test grace period
- Provide recommendations

### 8. Copy Protection Management

**Purpose:** Manage copy protection features

```bash
php artisan license:copy-protection --status
php artisan license:copy-protection --enable
php artisan license:copy-protection --disable
```

---

## 🔐 Security Best Practices

### 1. Use Stealth Mode in Production

```env
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
```

### 2. Secure License Server

Use HTTPS for your license server:
```env
LICENSE_SERVER=https://license.yourdomain.com
```

### 3. Keep API Token Secret

Never commit `.env` file to version control:
```gitignore
.env
.env.backup
```

### 4. Regular Monitoring

Check license status regularly:
```bash
php artisan license:client-status --check
```

### 5. Protect Sensitive Routes

Use middleware on all sensitive routes:
```php
Route::middleware(['auth', 'license'])->group(function () {
    // Protected routes
});
```

---

## 🐛 Troubleshooting

### Issue: "License validation failed"

**Solution 1:** Check your license key
```bash
php artisan license:info
```

**Solution 2:** Reset cache
```bash
php artisan license:reset-cache
```

**Solution 3:** Diagnose the issue
```bash
php artisan license:diagnose
```

### Issue: "Hardware fingerprint changed"

**Solution:** This can happen during deployment
```bash
php artisan license:deployment
```

### Issue: "Server unreachable"

**Solutions:**
1. Check server URL in `.env`
2. Verify network connectivity
3. Check firewall rules
4. Test manually:
```bash
curl https://license.acecoderz.com/api/heartbeat
```

### Issue: "Invalid checksum"

**Solution:** This is a rare compatibility issue
```bash
# Update package
composer update acecoderz/license-manager

# Clear cache
php artisan config:clear
php artisan cache:clear
php artisan license:reset-cache
```

---

## 📊 Monitoring

### Check License Status

```bash
php artisan license:client-status --check
```

### Monitor Logs

```bash
tail -f storage/logs/laravel.log | grep license
```

### Check Validation History

```php
use Illuminate\Support\Facades\Cache;

// Get validation history
$history = Cache::get('license_validations', []);
```

---

## 🔄 Updates and Maintenance

### Update the Package

```bash
composer update acecoderz/license-manager
php artisan package:discover --ansi
php artisan config:clear
```

### Check for Issues

```bash
php artisan license:diagnose
```

### Reset Configuration

```bash
php artisan config:clear
php artisan cache:clear
php artisan license:reset-cache
```

---

## 💡 Tips and Tricks

### 1. Graceful Degradation

License manager works offline if:
- License was recently validated
- Grace period hasn't expired
- Cache is still valid

### 2. Development Environment

For local development:
```env
LICENSE_SERVER=http://license-server.test
LICENSE_STEALTH_MODE=false
```

### 3. Performance Optimization

```env
# Reduce cache duration for testing
LICENSE_CACHE_DURATION=30

# Increase for production
LICENSE_CACHE_DURATION=1440
```

### 4. Custom Error Pages

Create custom views:
```bash
php artisan make:view errors.license-invalid
```

```php
<!-- resources/views/errors/license-invalid.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="alert alert-warning">
        <h4>License Validation Failed</h4>
        <p>Please contact support for assistance.</p>
    </div>
@endsection
```

---

## 📞 Support

### Documentation
- GitHub: [https://github.com/Sudhir-Pratap/license-manager](https://github.com/Sudhir-Pratap/license-manager)
- Support Email: support@acecoderz.com

### Commands for Support

When reporting issues, run:

```bash
php artisan license:diagnose > diagnosis.txt
php artisan license:info > license-info.txt
php artisan license:client-status --check > status.txt
```

Send these files to support for faster resolution.

---

## ✅ Checklist for New Projects

- [ ] Install package via composer
- [ ] Publish configuration
- [ ] Add environment variables
- [ ] Get license key from provider
- [ ] Run stealth installation
- [ ] Test license validation
- [ ] Add middleware to routes
- [ ] Configure stealth mode
- [ ] Test in production environment
- [ ] Monitor logs regularly

---

## 🎉 You're Ready!

Your license manager is now installed and configured. The package will:
- ✅ Validate licenses automatically
- ✅ Protect against piracy
- ✅ Work transparently for users
- ✅ Handle deployments gracefully
- ✅ Provide detailed diagnostics

**Happy Licensing!** 🚀

