# Acecoderz License Manager - Complete Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Basic Setup](#basic-setup)
5. [Middleware Integration](#middleware-integration)
6. [Vendor Protection Setup](#vendor-protection-setup)
7. [Available Commands](#available-commands)
8. [Security Features](#security-features)
9. [Deployment Guide](#deployment-guide)
10. [Troubleshooting](#troubleshooting)
11. [Advanced Usage](#advanced-usage)

---

## Introduction

Acecoderz License Manager is an enterprise-grade license validation package for Laravel applications. It provides comprehensive protection against unauthorized use while maintaining transparency for legitimate users.

### Key Features

- ✅ **Seamless Integration**: Zero disruption to legitimate users
- ✅ **Advanced Protection**: Multi-layered anti-piracy detection
- ✅ **Stealth Operation**: Invisible protection for production
- ✅ **No Dependencies**: Self-contained with file-based storage
- ✅ **Client-Friendly**: Built-in status checking and diagnostics
- ✅ **Deployment Safe**: Automatic handling of hosting environment changes
- ✅ **Legal Evidence**: Comprehensive violation tracking and reporting
- ✅ **Vendor Protection**: Automatic detection of vendor file tampering

---

## Installation

### Step 1: Install via Composer

```bash
composer require acecoderz/license-manager
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider"
```

This will create a configuration file at `config/license-manager.php`.

### Step 3: Verify Installation

Check if the package is properly installed:

```bash
php artisan list | grep license
```

You should see several license-related commands available.

---

## Configuration

### Step 1: Environment Variables

Add the following variables to your `.env` file:

```env
# Required License Configuration
LICENSE_KEY=your_generated_license_key
LICENSE_PRODUCT_ID=your_product_id
LICENSE_CLIENT_ID=your_client_id
LICENSE_SERVER=https://license.acecoderz.com
LICENSE_API_TOKEN=your_secure_api_token

# Optional Configuration
LICENSE_CACHE_DURATION=1440
LICENSE_SECURITY_HASH=your_security_hash
LICENSE_SUPPORT_EMAIL=support@acecoderz.com
LICENSE_AUTO_MIDDLEWARE=false
LICENSE_DISABLE_LOCAL_BYPASS=false

# Stealth Mode Configuration
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
LICENSE_BACKGROUND_VALIDATION=true
LICENSE_VALIDATION_TIMEOUT=5
LICENSE_GRACE_PERIOD=72
LICENSE_SILENT_FAIL=true
LICENSE_DEFERRED_ENFORCEMENT=true

# Vendor Protection
LICENSE_VENDOR_PROTECTION=true
LICENSE_VENDOR_INTEGRITY_CHECKS=true
LICENSE_VENDOR_FILE_LOCKING=true
LICENSE_VENDOR_DECOY_FILES=true
LICENSE_VENDOR_MONITOR_INTERVAL=300

# Deployment Configuration
LICENSE_BIND_DOMAIN_ONLY=false
LICENSE_CANONICAL_DOMAIN=
LICENSE_INSTALLATION_ID=
LICENSE_FORCE_REGENERATE_FINGERPRINT=false
```

### Step 2: Generate License Key

You need to obtain a license key from your license server. The license key should be generated on your license server and provided to clients.

### Step 3: Verify Configuration

Check your configuration:

```bash
php artisan license:info
```

This will display your current license configuration and installation details.

---

## Basic Setup

### Step 1: Configure Stealth Mode (Recommended)

For production environments, enable stealth mode for invisible operation:

```bash
php artisan license:stealth-install --config
```

This will show you the recommended configuration. Then enable it:

```bash
php artisan license:stealth-install --enable
```

Or manually add to `.env`:

```env
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
LICENSE_BACKGROUND_VALIDATION=true
LICENSE_SILENT_FAIL=true
```

### Step 2: Setup Vendor Protection

Protect your vendor directory from tampering:

```bash
php artisan license:vendor-protect --setup
```

This will:
- Create integrity baselines for vendor files
- Set up file locking
- Create decoy files for tampering detection
- Enable monitoring

### Step 3: Verify Setup

Check that everything is configured correctly:

```bash
php artisan license:stealth-install --check
php artisan license:vendor-protect --verify
```

---

## Middleware Integration

### Option 1: Manual Middleware Registration (Recommended)

#### In `app/Http/Kernel.php` (Laravel 9/10):

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware::class,
    ],
];
```

#### In `bootstrap/app.php` (Laravel 11+):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware::class,
    ]);
})
```

### Option 2: Route-Specific Middleware

Apply middleware to specific routes:

```php
use Illuminate\Support\Facades\Route;

// Stealth mode (invisible to users)
Route::middleware(['stealth-license'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Standard anti-piracy middleware
Route::middleware(['anti-piracy'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// Basic license middleware
Route::middleware(['license'])->group(function () {
    Route::get('/premium', [PremiumController::class, 'index']);
});
```

### Option 3: Auto-Registration (Not Recommended for Production)

Set in `.env`:

```env
LICENSE_AUTO_MIDDLEWARE=true
```

This automatically registers middleware globally. Use with caution.

### Middleware Types

1. **`stealth-license`**: Silent operation, no user-visible errors
2. **`anti-piracy`**: Comprehensive anti-piracy checks with detailed logging
3. **`license`**: Basic license validation

---

## Vendor Protection Setup

### Step 1: Initial Setup

Run the vendor protection setup command:

```bash
php artisan license:vendor-protect --setup
```

This command will:
- Create SHA-256 integrity baselines for all vendor files
- Set restrictive file permissions on critical files
- Create decoy files for tampering detection
- Enable real-time monitoring

**Important**: Run this command immediately after installing the package via Composer.

### Step 2: Verify Integrity

Regularly check vendor integrity:

```bash
php artisan license:vendor-protect --verify
```

### Step 3: Generate Tampering Report

View detailed tampering reports:

```bash
php artisan license:vendor-protect --report
```

This generates a JSON report saved to `storage/vendor-protection-report-{timestamp}.json`.

### Step 4: Automatic Monitoring

Vendor integrity is automatically checked:
- During every license validation
- Every 5 minutes (configurable)
- On application boot (if configured)

### What Happens When Tampering is Detected?

1. **Minor Tampering**:
   - Enhanced monitoring activated
   - Warnings logged
   - Email alerts sent

2. **Critical Tampering**:
   - License suspended for 24 hours
   - Immediate remote alerts
   - Detailed violation logs

3. **Severe Violations**:
   - Application termination (if enabled)
   - Full security lockdown
   - Complete violation report

---

## Available Commands

### License Information Commands

#### `license:info`
Display current license configuration and installation details:

```bash
php artisan license:info
```

#### `license:client-status`
Client-friendly status check:

```bash
# Check overall status
php artisan license:client-status --check

# Test system functionality
php artisan license:client-status --test
```

### Setup Commands

#### `license:stealth-install`
Configure stealth mode:

```bash
# Generate configuration
php artisan license:stealth-install --config

# Check current setup
php artisan license:stealth-install --check

# Enable stealth mode
php artisan license:stealth-install --enable

# Disable stealth mode
php artisan license:stealth-install --disable
```

#### `license:vendor-protect`
Manage vendor protection:

```bash
# Setup vendor protection
php artisan license:vendor-protect --setup

# Verify integrity
php artisan license:vendor-protect --verify

# Generate tampering report
php artisan license:vendor-protect --report

# Restore from backup (dangerous)
php artisan license:vendor-protect --restore
```

### Deployment Commands

#### `license:deployment`
Troubleshoot deployment issues:

```bash
# Check deployment status
php artisan license:deployment --check

# Attempt to fix issues
php artisan license:deployment --fix

# Regenerate hardware fingerprint
php artisan license:deployment --regenerate

# Test license validation
php artisan license:deployment --test
```

### Diagnostic Commands

#### `license:diagnose`
Diagnose license issues:

```bash
php artisan license:diagnose
```

#### `license:security-audit`
Run comprehensive security audit:

```bash
php artisan license:security-audit
```

### Utility Commands

#### `license:reset-cache`
Clear license validation cache:

```bash
php artisan license:reset-cache
```

#### `license:test-anti-piracy`
Test anti-piracy detection:

```bash
php artisan license:test-anti-piracy
```

---

## Security Features

### 1. License Validation

- **Server-based validation**: Validates against remote license server
- **Hardware fingerprinting**: Ties license to specific hardware
- **Domain/IP binding**: Restricts license to specific domains/IPs
- **Installation tracking**: Monitors installation count and locations

### 2. Anti-Piracy Detection

- **Geographic clustering**: Detects suspicious geographic patterns
- **VPN/Proxy detection**: Identifies proxy/VPN usage
- **Usage pattern analysis**: Monitors for reselling behavior
- **File integrity checks**: Verifies critical files haven't been modified

### 3. Vendor Protection

- **Integrity baselines**: SHA-256 hashes of all vendor files
- **File locking**: Restrictive permissions on critical files
- **Decoy files**: Hidden files to detect tampering attempts
- **Real-time monitoring**: Continuous integrity verification
- **Automatic response**: License suspension on tampering detection

### 4. Code Protection

- **Watermarking**: Invisible watermarks in output
- **Runtime checks**: Integrity verification during execution
- **Dynamic validation**: Changing validation keys
- **Anti-debugging**: Protection against debugging tools

### 5. Stealth Operation

- **Silent validation**: Background checks without user impact
- **Graceful degradation**: Continues operation when server unreachable
- **Hidden errors**: No visible error messages to users
- **Deferred enforcement**: Delays enforcement for better UX

---

## Deployment Guide

### Pre-Deployment Checklist

1. ✅ License key configured
2. ✅ Product ID and Client ID set
3. ✅ License server URL configured
4. ✅ API token configured
5. ✅ Stealth mode enabled (for production)
6. ✅ Vendor protection setup completed

### Step 1: Configure Deployment Settings

Add to `.env`:

```env
# For domain-based licensing (recommended for deployments)
LICENSE_BIND_DOMAIN_ONLY=true
LICENSE_CANONICAL_DOMAIN=yourdomain.com

# For deployment with hardware changes
LICENSE_FORCE_REGENERATE_FINGERPRINT=false
```

### Step 2: Check Deployment Status

Before deploying:

```bash
php artisan license:deployment --check
```

### Step 3: Handle Deployment Issues

If moving to a new server:

```bash
# Regenerate hardware fingerprint
php artisan license:deployment --regenerate

# Get new installation details
php artisan license:info

# Update license on server with new fingerprint
```

### Step 4: Post-Deployment Verification

After deployment:

```bash
# Test license validation
php artisan license:deployment --test

# Verify vendor integrity
php artisan license:vendor-protect --verify

# Check client status
php artisan license:client-status --check
```

### Deployment Scenarios

#### Scenario 1: Moving to New Server

1. Run `php artisan license:deployment --regenerate`
2. Get new hardware fingerprint from `php artisan license:info`
3. Update license on server with new fingerprint
4. Deploy application
5. Verify with `php artisan license:deployment --test`

#### Scenario 2: Domain Change

1. Set `LICENSE_BIND_DOMAIN_ONLY=true`
2. Set `LICENSE_CANONICAL_DOMAIN=newdomain.com`
3. Update license on server with new domain
4. Deploy application
5. Verify with `php artisan license:client-status --check`

#### Scenario 3: Load Balancer / CDN

1. Use domain-based licensing (`LICENSE_BIND_DOMAIN_ONLY=true`)
2. Set `LICENSE_CANONICAL_DOMAIN` to your main domain
3. Ensure license server can reach your application
4. Configure graceful period for IP changes

---

## Troubleshooting

### Issue: License Validation Fails

**Symptoms**: License validation returns false

**Solutions**:

1. Check configuration:
   ```bash
   php artisan license:info
   ```

2. Verify license server connectivity:
   ```bash
   php artisan license:deployment --test
   ```

3. Check API token:
   - Verify `LICENSE_API_TOKEN` is correct
   - Ensure token has proper permissions on license server

4. Verify hardware fingerprint:
   ```bash
   php artisan license:deployment --check
   ```

5. Clear cache and retry:
   ```bash
   php artisan license:reset-cache
   ```

### Issue: Vendor Tampering Detected (False Positive)

**Symptoms**: Vendor protection reports tampering after legitimate updates

**Solutions**:

1. Verify what changed:
   ```bash
   php artisan license:vendor-protect --report
   ```

2. If legitimate update:
   - Run `php artisan license:vendor-protect --setup` to recreate baseline
   - Ensure updates are done through proper channels

3. If false positive:
   - Check if Composer updated dependencies
   - Verify file permissions haven't changed
   - Check if decoy files were accidentally modified

### Issue: Application Blocked After Deployment

**Symptoms**: Application shows license error after moving to new server

**Solutions**:

1. Check deployment status:
   ```bash
   php artisan license:deployment --check
   ```

2. Regenerate hardware fingerprint:
   ```bash
   php artisan license:deployment --regenerate
   ```

3. Update license on server with new fingerprint:
   - Get fingerprint from `php artisan license:info`
   - Update license record on license server

4. Fix deployment issues:
   ```bash
   php artisan license:deployment --fix
   ```

### Issue: Stealth Mode Not Working

**Symptoms**: License errors visible to users

**Solutions**:

1. Check stealth configuration:
   ```bash
   php artisan license:stealth-install --check
   ```

2. Verify middleware is registered:
   - Check `app/Http/Kernel.php` or `bootstrap/app.php`
   - Ensure `stealth-license` middleware is applied

3. Enable stealth mode:
   ```bash
   php artisan license:stealth-install --enable
   ```

4. Verify `.env` settings:
   ```env
   LICENSE_STEALTH_MODE=true
   LICENSE_SILENT_FAIL=true
   LICENSE_DEFERRED_ENFORCEMENT=true
   ```

### Issue: Too Many Validation Requests

**Symptoms**: High number of license validation requests

**Solutions**:

1. Increase cache duration:
   ```env
   LICENSE_CACHE_DURATION=2880  # 48 hours
   ```

2. Enable background validation:
   ```env
   LICENSE_BACKGROUND_VALIDATION=true
   ```

3. Enable stealth mode:
   ```env
   LICENSE_STEALTH_MODE=true
   ```

### Issue: License Server Unreachable

**Symptoms**: Application blocked when license server is down

**Solutions**:

1. Increase grace period:
   ```env
   LICENSE_GRACE_PERIOD=168  # 7 days
   ```

2. Enable background validation:
   ```env
   LICENSE_BACKGROUND_VALIDATION=true
   ```

3. Enable stealth mode:
   ```env
   LICENSE_STEALTH_MODE=true
   LICENSE_SILENT_FAIL=true
   ```

---

## Advanced Usage

### Custom Validation Logic

You can extend the license validation with custom logic:

```php
use Acecoderz\LicenseManager\LicenseManager;

$licenseManager = app(LicenseManager::class);

$isValid = $licenseManager->validateLicense(
    $licenseKey,
    $productId,
    $domain,
    $ip,
    $clientId
);

if ($isValid) {
    // Your custom logic here
}
```

### Custom Middleware

Create custom middleware extending the base classes:

```php
namespace App\Http\Middleware;

use Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware;

class CustomLicenseMiddleware extends StealthLicenseMiddleware
{
    public function handle($request, Closure $next)
    {
        // Custom logic before validation
        $result = parent::handle($request, $next);
        // Custom logic after validation
        return $result;
    }
}
```

### Integration with License Server

Ensure your license server implements the following endpoint:

```
POST /api/validate
```

**Request Body**:
```json
{
    "license_key": "string",
    "product_id": "string",
    "domain": "string",
    "ip": "string",
    "client_id": "string",
    "checksum": "string",
    "hardware_fingerprint": "string",
    "installation_id": "string"
}
```

**Response**:
```json
{
    "valid": true,
    "message": "License valid",
    "expires_at": "2024-12-31T23:59:59Z"
}
```

### Monitoring and Logging

The package logs all validation attempts and security events:

- **License validation**: `storage/logs/laravel.log`
- **Security events**: `storage/logs/laravel.log`
- **Tampering reports**: `storage/vendor-protection-report-*.json`

Enable remote logging:

```env
LICENSE_REMOTE_SECURITY_LOGGING=true
```

### Custom Security Responses

Configure custom responses for different security levels:

```php
// In config/license-manager.php
'vendor_protection' => [
    'terminate_on_critical' => true, // Terminate app on critical tampering
    'self_healing' => false, // Enable automatic restoration
],
```

---

## Best Practices

1. **Always enable stealth mode in production** to avoid user-visible errors
2. **Setup vendor protection immediately** after installation
3. **Use domain-based licensing** for deployments with changing IPs
4. **Monitor tampering reports regularly** to catch security issues early
5. **Keep license server accessible** to ensure proper validation
6. **Use graceful periods** for deployments to avoid service interruption
7. **Test license validation** after any deployment or configuration change
8. **Backup vendor baselines** before major updates
9. **Use deployment commands** to troubleshoot issues quickly
10. **Keep API tokens secure** and rotate them regularly

---

## Support

For issues, questions, or support:

- **Email**: support@acecoderz.com
- **Documentation**: Check this file and `README.md`
- **Security Issues**: See `SECURITY_CHECKLIST.md`

---

## License

This package is licensed under the MIT License. See `LICENSE.md` for details.

