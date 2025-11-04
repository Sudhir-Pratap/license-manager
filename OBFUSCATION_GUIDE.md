# Code Obfuscation & Protection - How It Works

## Overview

The Acecoderz License Manager implements multiple layers of code protection and obfuscation to prevent tampering, reverse engineering, and unauthorized modification of license validation logic.

---

## Protection Layers

### 1. **Code Obfuscation** (`CodeProtectionService`)

#### What It Does:
- Renames critical function names to random strings
- Makes code harder to read and understand
- Protects sensitive license validation logic

#### How It Works:

```php
// Before Obfuscation:
public function validateLicense(...) { ... }
public function generateHardwareFingerprint() { ... }

// After Obfuscation:
public function vlA3b9X2m(...) { ... }  // validateLicense renamed
public function ghfK7p4Q1(...) { ... }  // generateHardwareFingerprint renamed
```

**Process:**
1. Scans critical files (`LicenseManager.php`, `AntiPiracyManager.php`)
2. Replaces function names with random strings
3. Stores mapping in cache for deobfuscation when needed
4. Makes reverse engineering much harder

**Configuration:**
```env
LICENSE_OBFUSCATE=true  # Enable/disable obfuscation
```

---

### 2. **Runtime Integrity Checks**

#### What It Does:
- Continuously monitors code files for modifications
- Generates SHA-256 hashes of critical files
- Compares current state with baseline

#### How It Works:

```php
// Generate integrity hash
$hash = hash('sha256', file_contents);

// Verify integrity
if ($currentHash !== $expectedHash) {
    // File was modified - trigger alert
    sendSecurityAlert();
    return false;
}
```

**Process:**
1. Generates hash of critical files on first run
2. Stores baseline hash in cache
3. Re-checks periodically (every hour by default)
4. Alerts if any file hash changes

**Protected Files:**
- `LicenseManager.php`
- `AntiPiracyManager.php`
- `config/license-manager.php`

**Configuration:**
```env
LICENSE_RUNTIME_CHECKS=true
LICENSE_INTEGRITY_CHECK_INTERVAL=3600  # seconds
```

---

### 3. **Watermarking** (`WatermarkingService`)

#### What It Does:
- Embeds invisible watermarks in HTML output
- Allows tracking of copied/pirated content
- Identifies source of unauthorized distribution

#### How It Works:

**A. Unicode Zero-Width Characters**
```php
// Creates invisible watermark using Unicode characters
$watermark = "\u{200B}\u{200C}\u{200D}\u{2060}";  // Invisible characters
```

**B. Multiple Embedding Locations:**

1. **HTML Comments:**
```html
<!-- A3b9X2mK7p4Q1 -->  <!-- Hidden watermark -->
```

2. **Meta Tags:**
```html
<meta name="wm" content="A3b9X2mK7p4Q1">  <!-- Invisible meta tag -->
```

3. **JavaScript Variables:**
```javascript
var _WM = "A3b9X2mK7p4Q1";  // Hidden JS variable
```

4. **CSS Comments:**
```css
/* A3b9X2mK7p4Q1 */  /* Hidden in CSS */
```

**Process:**
1. Generates unique watermark from `clientId + licenseKey + date`
2. Embeds watermark in multiple locations
3. Watermark changes daily (prevents removal)
4. If watermarks are missing, alerts security team

**Configuration:**
```env
LICENSE_WATERMARK=true
```

---

### 4. **Runtime JavaScript Checks**

#### What It Does:
- Client-side integrity validation
- Detects developer tools and debugging
- Prevents tampering in browser

#### How It Works:

```javascript
// Injected into every HTML page
(function() {
    // Check for developer tools
    if (console.firebug) { return false; }
    
    // Check for debugger
    var start = new Date();
    debugger;
    var end = new Date();
    if (end - start > 100) {
        // Debugger detected - hide content
        document.body.style.display = 'none';
    }
    
    // Verify watermark exists
    var meta = document.querySelector('meta[name="wm"]');
    if (!meta) { 
        // Watermark removed - report to server
        reportViolation();
    }
    
    // Periodic checks every 30 seconds
    setInterval(checkIntegrity, 30000);
})();
```

**Checks Performed:**
- Developer tools detection (Chrome DevTools, Firebug)
- Debugger detection
- Watermark presence verification
- Page structure validation
- Time-based validation

**Configuration:**
```env
LICENSE_RUNTIME_CHECKS=true
```

---

### 5. **Anti-Debugging Measures**

#### What It Does:
- Detects debugging environments
- Prevents code analysis
- Detects development tools

#### How It Works:

**Server-Side Detection:**
```php
// Check for debugging extensions
$debugIndicators = [
    'xdebug' => extension_loaded('xdebug'),
    'debug_backtrace' => count(debug_backtrace()) > 10,
    'eval_usage' => detectEvalUsage(),
    'development_headers' => detectDevelopmentHeaders(),
];

if (array_filter($debugIndicators)) {
    // Log warning and potentially block
    Log::warning('Debugging environment detected');
}
```

**Client-Side Detection:**
```javascript
// Detect Chrome DevTools
if (window.chrome && window.chrome.runtime) {
    // DevTools detected
}

// Detect debugger breakpoints
var start = new Date();
debugger;
var end = new Date();
if (end - start > 100) {
    // Debugger paused execution - hide content
    document.body.style.display = 'none';
}
```

**Configuration:**
```env
LICENSE_ANTI_DEBUG=true
```

---

### 6. **Dynamic Validation Keys**

#### What It Does:
- Generates time-based validation keys
- Keys change periodically
- Prevents static key extraction

#### How It Works:

```php
// Generate dynamic keys based on time and client data
$keys = [
    'session_key' => hash('sha256', $clientId . $timestamp . 'session'),
    'validation_token' => hash('sha256', $licenseKey . $timestamp . $clientId),
    'integrity_hash' => hash('sha256', $clientId . $timestamp . 'integrity'),
];

// Keys expire after 15 minutes
Cache::put("dynamic_key_session", $keys['session_key'], now()->addMinutes(15));
```

**Process:**
1. Generates keys every 15 minutes
2. Keys based on clientId, licenseKey, and timestamp
3. Old keys expire automatically
4. Makes static key extraction impossible

**Configuration:**
```env
LICENSE_DYNAMIC_VALIDATION=true
```

---

### 7. **Data Obfuscation**

#### What It Does:
- Encrypts sensitive data before storage
- Multiple encoding layers
- Prevents plaintext data exposure

#### How It Works:

```php
// Obfuscate sensitive data
$data = ['client_id' => 'ABC123', 'license_key' => 'XYZ789'];

// Step 1: Encrypt with AES-128-CBC
$encrypted = openssl_encrypt(json_encode($data), 'AES-128-CBC', $key, 0, $iv);

// Step 2: Base64 encode
$encoded = base64_encode($encrypted);

// Step 3: Rot13 encoding (additional layer)
$obfuscated = str_rot13($encoded);

// Result: Multiple layers of protection
```

**Deobfuscation:**
```php
// Reverse process
$decoded = str_rot13($obfuscated);
$decrypted = openssl_decrypt(base64_decode($decoded), 'AES-128-CBC', $key, 0, $iv);
$data = json_decode($decrypted, true);
```

**Configuration:**
Automatically enabled for sensitive data operations.

---

## Complete Protection Flow

### 1. **Installation Phase:**
```
1. Package installed via Composer
2. Code obfuscation applied to critical files
3. Integrity baseline created
4. Watermarking system initialized
```

### 2. **Runtime Phase:**
```
Every Request:
├── Middleware executes
├── Runtime integrity check
├── Dynamic key validation
├── Anti-debug check
└── Watermark embedding

Every 30 seconds (Client-side):
├── JavaScript integrity check
├── Watermark verification
├── Debugger detection
└── Violation reporting
```

### 3. **Validation Phase:**
```
License Validation:
├── Check code integrity (file hashes)
├── Verify dynamic keys
├── Validate watermarks
├── Check for debugging tools
└── Server-side validation
```

---

## Security Features Summary

| Feature | Purpose | Location |
|---------|---------|----------|
| **Code Obfuscation** | Rename functions | Server-side |
| **Integrity Checks** | Detect file modifications | Server-side |
| **Watermarking** | Track copied content | Client-side (HTML) |
| **Runtime Checks** | Client-side validation | Browser (JavaScript) |
| **Anti-Debug** | Prevent debugging | Server + Client |
| **Dynamic Keys** | Time-based validation | Server-side |
| **Data Obfuscation** | Encrypt sensitive data | Server-side |

---

## Configuration

### Enable All Protection:
```env
LICENSE_OBFUSCATE=true
LICENSE_WATERMARK=true
LICENSE_RUNTIME_CHECKS=true
LICENSE_DYNAMIC_VALIDATION=true
LICENSE_ANTI_DEBUG=true
```

### Disable Specific Features:
```env
LICENSE_OBFUSCATE=false        # Disable code obfuscation
LICENSE_WATERMARK=false        # Disable watermarking
LICENSE_RUNTIME_CHECKS=false  # Disable runtime checks
LICENSE_ANTI_DEBUG=false      # Disable anti-debug
```

---

## How Clients Can't Bypass Protection

### 1. **Code Obfuscation:**
- Function names are randomized
- Hard to find validation logic
- Requires complete reverse engineering

### 2. **Integrity Checks:**
- File modifications detected immediately
- Baseline stored securely
- Changes trigger alerts

### 3. **Watermarking:**
- Multiple invisible watermarks
- Daily changes prevent removal
- Missing watermarks = violation

### 4. **Runtime Checks:**
- JavaScript runs in browser
- Can't be easily disabled
- Reports violations to server

### 5. **Dynamic Keys:**
- Keys change every 15 minutes
- Based on timestamp + client data
- Static extraction impossible

### 6. **Anti-Debug:**
- Detects debugging tools
- Blocks analysis attempts
- Logs suspicious activity

---

## Detection & Response

### When Tampering Detected:

1. **Immediate Actions:**
   - Log critical security event
   - Send alert to remote logger
   - Reduce license cache duration
   - Force immediate server validation

2. **Severity Levels:**

   **Low Severity:**
   - Missing watermark
   - Debugging tools detected
   - Response: Warning + Enhanced monitoring

   **Medium Severity:**
   - File integrity violation
   - Dynamic key mismatch
   - Response: Enhanced validation + Alert

   **High Severity:**
   - Code obfuscation bypassed
   - Multiple protection layers bypassed
   - Response: License suspension + Critical alert

---

## Best Practices

1. **Always Enable All Protection:**
   ```env
   LICENSE_OBFUSCATE=true
   LICENSE_WATERMARK=true
   LICENSE_RUNTIME_CHECKS=true
   LICENSE_ANTI_DEBUG=true
   ```

2. **Monitor Security Logs:**
   - Check for integrity violations
   - Review watermark detection reports
   - Monitor debugging attempts

3. **Regular Updates:**
   - Update obfuscation mappings
   - Regenerate integrity baselines
   - Rotate dynamic keys

4. **Client Communication:**
   - Inform clients about protection
   - Explain legitimate use cases
   - Provide troubleshooting guide

---

## Technical Details

### File Modification Detection:
- Uses SHA-256 hashing
- Compares against baseline
- Detects even minor changes

### Watermark Generation:
- Uses Unicode zero-width characters
- Base64 encoded
- Daily rotation prevents removal

### Dynamic Key Generation:
- SHA-256 hash of multiple components
- Time-based expiration
- Cache-based storage

### Anti-Debug Detection:
- Checks for Xdebug extension
- Monitors debug_backtrace depth
- Detects development headers
- JavaScript debugger detection

---

## Summary

The obfuscation and protection system provides **multi-layered security**:

1. **Code is obfuscated** - Hard to read and understand
2. **Files are monitored** - Changes detected immediately
3. **Content is watermarked** - Tracks unauthorized distribution
4. **Runtime checks** - Client-side validation
5. **Anti-debugging** - Prevents analysis
6. **Dynamic keys** - Time-based validation

Together, these layers make it extremely difficult to bypass or tamper with the license validation system while maintaining transparency for legitimate users.

