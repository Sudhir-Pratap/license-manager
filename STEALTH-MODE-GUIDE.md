# Stealth Mode Guide

## What is Stealth Mode?

**Stealth Mode** is a **transparent license validation system** that operates **invisibly** in the background without interrupting user experience. It's designed for production environments where you want license protection but don't want users to be aware of it.

## How Does It Work?

### Key Characteristics:

1. **Silent Operation**
   - No visible errors to end users
   - License validation happens in background
   - Graceful degradation if server is unreachable

2. **Fast & Lightweight**
   - Quick 5-second timeout (vs 15 seconds normal)
   - Cached validation results (15-30 minutes)
   - Deferred validation (validates after page loads)

3. **Transparent to Users**
   - No blocking errors
   - No license status UI
   - Application continues running even if validation fails

4. **Admin Monitoring**
   - Logs go to separate log channels
   - Suspicious activity tracked silently
   - Admin dashboard for monitoring

## Configuration

### Enable Stealth Mode

Add to your `.env`:
```env
LICENSE_STEALTH_MODE=true
```

### All Stealth Options:

```env
# Stealth Mode Configuration
LICENSE_STEALTH_MODE=true                    # Enable stealth mode
LICENSE_HIDE_UI=true                         # Hide license UI elements
LICENSE_MUTE_LOGS=true                       # Suppress logs from client view
LICENSE_BACKGROUND_VALIDATION=true           # Validate in background
LICENSE_VALIDATION_TIMEOUT=5                 # Quick timeout (seconds)
LICENSE_GRACE_PERIOD=72                      # Hours of grace when server unreachable
LICENSE_SILENT_FAIL=true                     # Don't show errors to client
LICENSE_DEFERRED_ENFORCEMENT=true            # Delay enforcement for UX
```

## How It Works Technically

### 1. **Caching Strategy**
```
First Request → Validate → Cache for 15 minutes
Subsequent Requests → Use cache → Fast, no delay
Every 30 minutes → Re-validate in background
```

### 2. **Validation Flow**

**Standard Mode:**
```
Request → Validate immediately → Block if invalid → Return error
```

**Stealth Mode:**
```
Request → Return response immediately → Validate in background → Log failures
```

### 3. **Grace Period Handling**

If license server is unreachable:
- **First failure**: Start 72-hour grace period
- **During grace**: Allow access, log for admin
- **After grace**: Can still allow (if silent_fail enabled) or show minimal error

### 4. **Error Handling**

**Standard Mode:**
```
Validation fails → Show error page → User sees: "Invalid license"
```

**Stealth Mode:**
```
Validation fails → Log for admin → Continue serving → Monitor silently
```

## Use Cases

### ✅ When to Use Stealth Mode

1. **Production Applications**
   - End users shouldn't see license errors
   - Better user experience
   - Professional appearance

2. **High Traffic Sites**
   - Fast validation (cached)
   - No blocking on requests
   - Graceful during server outages

3. **SaaS Applications**
   - Transparent license management
   - Silent monitoring
   - Non-intrusive protection

4. **Client-Facing Applications**
   - Clients don't see license UI
   - Clean interface
   - Professional experience

### ❌ When NOT to Use Stealth Mode

1. **Development/Testing**
   - You want to see errors immediately
   - Need detailed logging
   - Want to debug license issues

2. **High Security Requirements**
   - Need immediate blocking on invalid licenses
   - Can't allow grace periods
   - Must enforce strictly

## Benefits

### 1. **Better User Experience**
- ✅ No blocking errors
- ✅ Fast page loads (cached validation)
- ✅ Application continues if server is down
- ✅ No license warnings interrupting workflow

### 2. **Production Ready**
- ✅ Graceful degradation
- ✅ Handles network issues
- ✅ Professional appearance
- ✅ Silent monitoring

### 3. **Performance**
- ✅ 5-second timeout (vs 15 seconds)
- ✅ Cached results (less server calls)
- ✅ Background validation (no blocking)
- ✅ Minimal impact on page load

### 4. **Security**
- ✅ Still validates licenses
- ✅ Monitors suspicious activity
- ✅ Logs violations (admin-only)
- ✅ Copy protection still active

## Setting Up Stealth Mode

### Step 1: Configure Environment

```env
# .env
LICENSE_STEALTH_MODE=true
LICENSE_SILENT_FAIL=true
LICENSE_DEFERRED_ENFORCEMENT=true
LICENSE_BACKGROUND_VALIDATION=true
LICENSE_VALIDATION_TIMEOUT=5
LICENSE_GRACE_PERIOD=72
```

### Step 2: Use Stealth Middleware (Optional)

If you want to use the dedicated stealth middleware:

```php
// routes/web.php
Route::middleware(['stealth-license'])->group(function () {
    // Your protected routes
});
```

Or use the auto-registration (already using `AntiPiracySecurity` which handles stealth mode automatically).

### Step 3: Configure Log Channels

In `config/logging.php`:

```php
'channels' => [
    'license' => [
        'driver' => 'daily',
        'path' => storage_path('logs/license.log'),
        'level' => 'debug',
    ],
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
    ],
],
```

## Monitoring

### View Stealth Logs

```bash
# License validation logs (separate from app logs)
tail -f storage/logs/license.log

# Security violations
tail -f storage/logs/security.log
```

### Check Stealth Status

```bash
php artisan license:stealth-install --check
```

This shows:
- Stealth mode enabled/disabled
- Current settings
- Middleware registration
- Recommendations

## Comparison: Standard vs Stealth Mode

| Feature | Standard Mode | Stealth Mode |
|---------|---------------|--------------|
| **Validation Speed** | 15s timeout | 5s timeout |
| **User Error Messages** | Visible | Hidden |
| **Page Blocking** | Blocks on fail | Continues |
| **Server Down** | Shows error | Grace period |
| **Caching** | Standard | Aggressive (15-30min) |
| **Logs** | App log | Separate channels |
| **UX Impact** | High (errors visible) | None (transparent) |
| **Admin Monitoring** | Basic | Enhanced |

## Best Practices

### 1. **Enable Grace Period**
```env
LICENSE_GRACE_PERIOD=72  # 3 days for maintenance windows
```

### 2. **Set Appropriate Timeout**
```env
LICENSE_VALIDATION_TIMEOUT=5  # Fast, but not too aggressive
```

### 3. **Monitor Separate Logs**
- Check `storage/logs/license.log` regularly
- Monitor `storage/logs/security.log` for violations
- Don't rely on app logs for license issues

### 4. **Test Grace Period**
- Simulate server outage
- Verify grace period works
- Test recovery after server comes back

## Troubleshooting

### Issue: "Validation still showing errors"

**Solution:** Make sure stealth mode is enabled:
```bash
php artisan config:clear
php artisan license:stealth-install --check
```

### Issue: "No logs visible"

**Solution:** Check separate log channels:
```bash
tail -f storage/logs/license.log
tail -f storage/logs/security.log
```

### Issue: "Validation too slow"

**Solution:** Check cache is working:
```bash
php artisan cache:clear
# Should speed up subsequent requests
```

## Real-World Example

### Before (Standard Mode):
```
User visits site → Validates license → Server timeout → 
ERROR: "License validation failed" → User blocked ❌
```

### After (Stealth Mode):
```
User visits site → Returns page immediately → Validates in background → 
Server timeout → Logged for admin → User continues ✅
```

## Summary

**Stealth Mode is ideal for:**
- ✅ Production applications
- ✅ Client-facing software
- ✅ High-traffic sites
- ✅ Better user experience
- ✅ Graceful error handling

**Key Benefits:**
1. **Transparent** - Users never see license errors
2. **Fast** - Cached validation, quick timeouts
3. **Reliable** - Grace periods for outages
4. **Secure** - Still validates, monitors silently
5. **Professional** - Clean, polished experience

**Default:** Stealth mode is **enabled by default** (`LICENSE_STEALTH_MODE=true`), which is the recommended setting for production.

