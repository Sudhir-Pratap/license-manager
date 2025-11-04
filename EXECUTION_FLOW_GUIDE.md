# 📋 Complete Execution Flow Guide

## Exact Order of Operations for License Manager Package

This guide shows the **exact order** you must follow to properly set up and use the license manager package without any issues.

---

## 🚀 Phase 1: Initial Installation & Setup

### Step 1: Install Package via Composer

```bash
composer require acecoderz/license-manager
```

**What happens:**
- Package is installed in `vendor/acecoderz/license-manager/`
- Service provider is auto-discovered
- Commands are registered

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider" --tag=config
```

**What happens:**
- Creates `config/license-manager.php`
- Copies default configuration

### Step 3: Verify Installation

```bash
php artisan list | grep license
```

**Expected output:**
```
license:client-status
license:deployment-license
license:diagnose
license:generate
license:info
license:obfuscate
license:reset-cache
license:security-audit
license:stealth-install
license:test-anti-piracy
license:vendor-protect
```

---

## ⚙️ Phase 2: Configuration

### Step 4: Add Environment Variables

Edit your `.env` file and add:

```env
# ============================================
# REQUIRED - License Server Configuration
# ============================================
LICENSE_KEY=your_generated_license_key_here
LICENSE_PRODUCT_ID=your_product_id_here
LICENSE_CLIENT_ID=your_client_id_here
LICENSE_SERVER=https://your-license-server.com/api
LICENSE_API_TOKEN=your_secure_api_token_here

# ============================================
# RECOMMENDED - Stealth Mode (Production)
# ============================================
LICENSE_STEALTH_MODE=true
LICENSE_HIDE_UI=true
LICENSE_MUTE_LOGS=true
LICENSE_BACKGROUND_VALIDATION=true
LICENSE_VALIDATION_TIMEOUT=5
LICENSE_SILENT_FAIL=true
LICENSE_DEFERRED_ENFORCEMENT=true

# ============================================
# RECOMMENDED - Code Protection
# ============================================
LICENSE_OBFUSCATE=true          # Enable code obfuscation
LICENSE_WATERMARK=true           # Enable watermarking
LICENSE_RUNTIME_CHECKS=true      # Enable runtime checks
LICENSE_ANTI_DEBUG=true          # Enable anti-debugging
LICENSE_DYNAMIC_VALIDATION=true  # Enable dynamic keys

# ============================================
# RECOMMENDED - Vendor Protection
# ============================================
LICENSE_VENDOR_PROTECTION=true
LICENSE_VENDOR_INTEGRITY_CHECKS=true
LICENSE_VENDOR_FILE_LOCKING=true
LICENSE_VENDOR_DECOY_FILES=true

# ============================================
# OPTIONAL - Advanced Configuration
# ============================================
LICENSE_CACHE_DURATION=1440           # Minutes (24 hours)
LICENSE_SUPPORT_EMAIL=support@yourdomain.com
LICENSE_AUTO_MIDDLEWARE=false         # Manual control recommended
LICENSE_DISABLE_LOCAL_BYPASS=false    # Allow local development
```

### Step 5: Verify Configuration

```bash
php artisan license:info
```

**Check output:**
- ✅ License key is set
- ✅ License server URL is correct
- ✅ All required configs are present

---

## 🔒 Phase 3: Code Obfuscation (Optional but Recommended)

### Step 6: Obfuscate Code (If Enabled)

**⚠️ CRITICAL: Run this BEFORE vendor protection setup!**

```bash
php artisan license:obfuscate --backup --verify
```

**What happens:**
1. Creates backup of vendor files (if `--backup` flag)
2. Obfuscates function names in critical files
3. **Automatically regenerates vendor integrity baseline** ✅
4. Verifies obfuscation was applied (if `--verify` flag)

**Expected output:**
```
🔒 Starting code obfuscation process...
📝 Obfuscating critical files...
✅ Successfully obfuscated 4 file(s)
🔄 Regenerating vendor integrity baseline...
✅ Vendor integrity baseline regenerated with obfuscated files
✅ Tampering detection will now expect obfuscated file hashes
🔍 Verifying obfuscation...
  ✓ LicenseManager.php - obfuscated
  ✓ AntiPiracyManager.php - obfuscated
✅ Verification: 2 file(s) confirmed obfuscated
⚠️  IMPORTANT: Vendor files have been modified.
⚠️  These changes will be lost on next composer update.
💡 Consider using a deployment script to re-obfuscate after updates.
```

**Files modified:**
- `vendor/acecoderz/license-manager/src/LicenseManager.php`
- `vendor/acecoderz/license-manager/src/AntiPiracyManager.php`
- `vendor/acecoderz/license-manager/src/Services/CodeProtectionService.php`
- `vendor/acecoderz/license-manager/src/Services/WatermarkingService.php`

---

## 🛡️ Phase 4: Vendor Protection Setup

### Step 7: Setup Vendor Protection

**⚠️ IMPORTANT:**
- If you ran obfuscation (Step 6), baseline is already created - you can skip this step
- If you skipped obfuscation, run this step now

```bash
php artisan license:vendor-protect --setup
```

**What happens:**
1. Creates integrity baseline for vendor files
2. Sets up file locking on critical files
3. Creates decoy files for tampering detection
4. Enables monitoring

**Expected output:**
```
🔒 Setting up vendor directory protection...
✅ Vendor protection setup completed successfully!

Protection measures implemented:
  ✓ Integrity baseline created
  ✓ File locking enabled
  ✓ Decoy files created
  ✓ Monitoring activated
```

### Step 8: Verify Vendor Protection

```bash
php artisan license:vendor-protect --verify
```

**Expected output:**
```
🔍 Verifying vendor directory integrity...
✅ Vendor integrity verified - no tampering detected
```

---

## 🔧 Phase 5: Middleware Integration

### Step 9: Register Middleware

**Choose ONE of the following options:**

#### Option A: Stealth Mode (Recommended for Production)

**Laravel 9/10** - Edit `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware::class,
    ],
];
```

**Laravel 11+** - Edit `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware::class,
    ]);
})
```

#### Option B: Standard License Security

**Laravel 9/10** - Edit `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Acecoderz\LicenseManager\Http\Middleware\LicenseSecurity::class,
    ],
];
```

**Laravel 11+** - Edit `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \Acecoderz\LicenseManager\Http\Middleware\LicenseSecurity::class,
    ]);
})
```

#### Option C: Route-Specific Protection

**In your routes file (`routes/web.php`):**

```php
Route::middleware(['license'])->group(function () {
    // Protected routes
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Or use alias
Route::middleware(['stealth-license'])->group(function () {
    // Protected routes with stealth mode
});
```

---

## 🧪 Phase 6: Testing & Verification

### Step 10: Test License Validation

```bash
php artisan license:test-anti-piracy
```

**Expected output:**
```
🧪 Testing Anti-Piracy Detection...
✅ License validation: PASSED
✅ Hardware fingerprint: GENERATED
✅ Server validation: CONNECTED
✅ Domain binding: VERIFIED
```

### Step 11: Run Security Audit

```bash
php artisan license:security-audit
```

**This checks:**
- ✅ Configuration validity
- ✅ License server connectivity
- ✅ Vendor protection status
- ✅ Code obfuscation status
- ✅ Middleware registration
- ✅ Watermarking status

### Step 12: Check Client Status (Client-Friendly)

```bash
php artisan license:client-status --check
```

**Shows client-friendly status without exposing sensitive details.**

---

## 🚀 Phase 7: Deployment

### Step 13: Deployment Script

Create a deployment script (`deploy.sh`):

```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Step 1: Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Step 2: Obfuscate code (if enabled)
if [ "$LICENSE_OBFUSCATE" = "true" ]; then
    echo "🔒 Obfuscating code..."
    php artisan license:obfuscate --backup --verify
fi

# Step 3: Setup vendor protection (if not already done)
if [ "$LICENSE_VENDOR_PROTECTION" = "true" ]; then
    echo "🛡️ Setting up vendor protection..."
    php artisan license:vendor-protect --setup
fi

# Step 4: Cache configuration
echo "💾 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 5: Verify deployment
echo "✅ Verifying deployment..."
php artisan license:security-audit

echo "🎉 Deployment completed successfully!"
```

### Step 14: Run Deployment

```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 🔄 Phase 8: Post-Deployment Maintenance

### Step 15: Monitor Vendor Integrity

**Set up periodic checks:**

```bash
# Add to cron (every hour)
0 * * * * cd /path/to/project && php artisan license:vendor-protect --verify >> /dev/null 2>&1
```

### Step 16: Generate Tampering Reports

```bash
php artisan license:vendor-protect --report
```

**Saves report to:** `storage/app/vendor-protection-report-YYYY-MM-DD-HH-MM-SS.json`

---

## 📊 Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    COMPLETE SETUP FLOW                      │
└─────────────────────────────────────────────────────────────┘

PHASE 1: INSTALLATION
├── composer require acecoderz/license-manager
├── php artisan vendor:publish --provider="..."
└── php artisan list | grep license (verify)

PHASE 2: CONFIGURATION
├── Add environment variables to .env
└── php artisan license:info (verify config)

PHASE 3: CODE OBFUSCATION ⚠️
├── php artisan license:obfuscate --backup --verify
└── [Baseline automatically regenerated]

PHASE 4: VENDOR PROTECTION
├── php artisan license:vendor-protect --setup (if no obfuscation)
└── php artisan license:vendor-protect --verify

PHASE 5: MIDDLEWARE
└── Register middleware in Kernel.php or bootstrap/app.php

PHASE 6: TESTING
├── php artisan license:test-anti-piracy
├── php artisan license:security-audit
└── php artisan license:client-status --check

PHASE 7: DEPLOYMENT
└── Run deployment script (includes all steps)

PHASE 8: MAINTENANCE
├── Monitor vendor integrity (cron)
└── Generate tampering reports (periodic)
```

---

## ⚠️ Critical Order Requirements

### ✅ CORRECT ORDER:

```
1. composer install
2. Configure .env
3. php artisan license:obfuscate (if enabled)
   └── This automatically creates baseline ✅
4. php artisan license:vendor-protect --setup (only if no obfuscation)
5. Register middleware
6. Test
7. Deploy
```

### ❌ WRONG ORDER (Will Cause Issues):

```
1. composer install
2. php artisan license:vendor-protect --setup
   └── Creates baseline with original files ❌
3. php artisan license:obfuscate
   └── Changes files → FALSE TAMPERING ALERT! ❌
```

**Why it fails:**
- Vendor protection creates baseline BEFORE obfuscation
- Obfuscation changes file hashes
- Integrity check detects changed hashes → triggers false alert

**Solution:**
- Run obfuscation FIRST (it regenerates baseline automatically)
- OR run vendor protection AFTER obfuscation

---

## 🔍 Quick Reference Checklist

Use this checklist to ensure you've completed all steps:

```
□ Phase 1: Installation
  □ Package installed via composer
  □ Configuration published
  □ Commands verified

□ Phase 2: Configuration
  □ LICENSE_KEY added to .env
  □ LICENSE_SERVER added to .env
  □ LICENSE_API_TOKEN added to .env
  □ Stealth mode configured (if needed)
  □ Code protection enabled (if needed)
  □ Vendor protection enabled (if needed)
  □ Configuration verified

□ Phase 3: Code Obfuscation (Optional)
  □ Obfuscation enabled in .env
  □ php artisan license:obfuscate executed
  □ Obfuscation verified

□ Phase 4: Vendor Protection
  □ Vendor protection enabled in .env
  □ Baseline created (automatically or manually)
  □ Vendor protection verified

□ Phase 5: Middleware
  □ Middleware registered in Kernel.php or bootstrap/app.php
  □ Routes protected (if needed)

□ Phase 6: Testing
  □ License validation tested
  □ Security audit passed
  □ Client status checked

□ Phase 7: Deployment
  □ Deployment script created
  □ All steps tested in staging
  □ Production deployment successful

□ Phase 8: Maintenance
  □ Monitoring configured
  □ Reports scheduled
```

---

## 🆘 Troubleshooting Common Issues

### Issue 1: False Tampering Alerts After Obfuscation

**Symptoms:**
- Vendor protection reports tampering after running obfuscation

**Cause:**
- Baseline was created BEFORE obfuscation

**Solution:**
```bash
# Regenerate baseline after obfuscation
php artisan license:obfuscate --verify
# This automatically regenerates the baseline
```

### Issue 2: Obfuscation Not Working

**Symptoms:**
- Function names still visible in vendor files

**Check:**
```bash
# Verify obfuscation is enabled
php artisan license:info

# Check vendor files
grep -n "function validateLicense" vendor/acecoderz/license-manager/src/LicenseManager.php
# Should return nothing if obfuscated
```

**Solution:**
```bash
# Re-run obfuscation
php artisan license:obfuscate --verify
```

### Issue 3: Middleware Not Executing

**Symptoms:**
- License validation not running

**Check:**
```bash
# Verify middleware is registered
php artisan route:list | grep license

# Check middleware configuration
php artisan license:info
```

**Solution:**
- Ensure middleware is registered in `Kernel.php` or `bootstrap/app.php`
- Clear route cache: `php artisan route:clear`

### Issue 4: Vendor Files Lost After Composer Update

**Symptoms:**
- Obfuscation removed after `composer update`

**Cause:**
- Vendor files are overwritten on update

**Solution:**
```bash
# Re-run obfuscation after update
composer update
php artisan license:obfuscate --backup --verify
```

---

## 📝 Summary

**Essential Order:**
1. ✅ Install → Configure → Obfuscate → Protect → Middleware → Test → Deploy

**Key Points:**
- Obfuscation MUST run before vendor protection (or vendor protection AFTER obfuscation)
- Obfuscation command automatically handles baseline regeneration
- Vendor files are modified - changes lost on composer update
- Always re-obfuscate after composer update/install

**Recommended Flow:**
```bash
# Initial setup
composer install
# Configure .env
php artisan license:obfuscate --backup --verify
php artisan license:vendor-protect --verify
# Register middleware
php artisan license:security-audit

# After composer update
composer update
php artisan license:obfuscate --backup --verify
```

This ensures everything works together seamlessly! 🎉
