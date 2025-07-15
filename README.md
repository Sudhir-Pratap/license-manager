# Enhanced Anti-Piracy License Manager

A comprehensive Laravel package designed to prevent software piracy and unauthorized reselling of your applications.

## 🛡️ Anti-Piracy Features

### 1. **Hardware Fingerprinting**
- Generates unique hardware fingerprints based on server characteristics
- Tracks file system paths, database configuration, and system resources
- Detects when software is moved to different hardware

### 2. **Installation Tracking**
- Unique installation IDs for each deployment
- Tracks multiple installations per license
- Prevents unauthorized redistribution

### 3. **Code Tampering Detection**
- Monitors critical files for modifications
- Detects middleware bypass attempts
- Validates file integrity

### 4. **Suspicious Activity Detection**
- Rate limiting and request frequency monitoring
- Hardware fingerprint change detection
- Multiple installation detection

### 5. **Enhanced Security**
- HMAC signatures for license keys
- Encrypted license storage
- Checksum verification
- IP blacklisting for repeated violations

## 🚀 Installation

### 1. Install the Package
```bash
composer require acecoderz/license-manager
```

### 2. Publish Configuration
```bash
php artisan vendor:publish --tag=config
```

### 3. Configure Environment Variables
```env
LICENSE_KEY=your_generated_license_key
LICENSE_PRODUCT_ID=your_product_id
LICENSE_CLIENT_ID=your_client_id
LICENSE_SERVER=https://your-license-server.com
LICENSE_API_TOKEN=your_api_token
LICENSE_CACHE_DURATION=1440
LICENSE_BYPASS_TOKEN=your_emergency_bypass_token
LICENSE_SUPPORT_EMAIL=support@yourcompany.com
```

### 4. Apply Middleware
Add to your `app/Http/Kernel.php`:
```php
protected $middleware = [
    // ... other middleware
    \Acecoderz\LicenseManager\Http\Middleware\AntiPiracySecurity::class,
];
```

## 🔧 Usage

### Basic License Validation
```php
use Acecoderz\LicenseManager\AntiPiracyManager;

$antiPiracyManager = app(AntiPiracyManager::class);
$isValid = $antiPiracyManager->validateAntiPiracy();
```

### Testing the System
```bash
# Test anti-piracy system
php artisan license:test-anti-piracy

# Detailed test with hardware fingerprint info
php artisan license:test-anti-piracy --detailed
```

### Generate License Keys
```bash
php artisan license:generate --product-id=AGENT_PANEL --domain=clientdomain.com --ip=192.168.1.100 --expiry="2024-12-31"
```

## 🏗️ License Server Setup

### 1. Database Migrations
```bash
php artisan migrate
```

### 2. Enhanced Routes
```php
// In license-server/routes/api.php
Route::post('/validate', [EnhancedLicenseController::class, 'validate']);
Route::post('/generate', [EnhancedLicenseController::class, 'generate']);
Route::post('/revoke', [EnhancedLicenseController::class, 'revoke']);
Route::get('/heartbeat', [EnhancedLicenseController::class, 'heartbeat']);
Route::get('/stats', [EnhancedLicenseController::class, 'getInstallationStats']);
```

### 3. Environment Configuration
```env
LICENSE_SECRET=your_license_secret_key
API_TOKEN=your_api_token
```

## 🔍 Monitoring and Detection

### Suspicious Activity Detection
The system automatically detects:
- Rapid license validation requests (>20/minute)
- Hardware fingerprint changes
- Multiple installations with same license
- Code tampering attempts
- IP-based attacks

### Logging
All activities are logged with detailed information:
```php
Log::info('License validation successful', [
    'product_id' => $productId,
    'domain' => $currentDomain,
    'ip' => $currentIp,
    'client_id' => $clientId,
    'hardware_fingerprint' => $hardwareFingerprint,
    'installation_id' => $installationId,
]);
```

## 🛡️ Security Measures

### 1. **Hardware Locking**
- License keys are tied to specific hardware configurations
- Moving software to different servers requires new license

### 2. **Installation Limits**
- Maximum 2 installations per license
- Tracks installation history and patterns

### 3. **Code Integrity**
- Monitors critical files for modifications
- Detects tampering with license middleware

### 4. **Network Security**
- Encrypted communications with license server
- Rate limiting to prevent abuse
- IP blacklisting for repeated violations

### 5. **Audit Trail**
- Complete logging of all license activities
- Installation tracking and statistics
- Suspicious activity alerts

## 🚨 Anti-Piracy Mechanisms

### 1. **Preventing Reselling**
- Hardware fingerprinting prevents simple copying
- Installation tracking detects multiple deployments
- License server validation on every request

### 2. **Preventing Piracy**
- Encrypted license keys with HMAC signatures
- Server-side validation with hardware fingerprinting
- Code tampering detection
- Automatic blacklisting of suspicious clients

### 3. **Detection Methods**
- Hardware fingerprint changes
- Multiple installation detection
- Rapid request detection
- Code modification detection
- Environment consistency checks

## 📊 Monitoring Dashboard

### Installation Statistics
```php
// Get installation stats
$stats = DB::table('installations')
    ->where('license_key', $licenseKey)
    ->selectRaw('COUNT(*) as total_installations, COUNT(DISTINCT domain) as unique_domains')
    ->first();
```

### License Server Health
```bash
# Check server communication
curl -H "Authorization: Bearer YOUR_TOKEN" https://your-license-server.com/api/heartbeat
```

## 🔧 Configuration Options

### Cache Duration
```env
LICENSE_CACHE_DURATION=1440  # 24 hours in minutes
```

### Rate Limiting
```php
// In license server
$this->middleware('throttle.client_id:50,1'); // 50 requests per minute
```

### Bypass Options
```env
LICENSE_BYPASS_TOKEN=your_emergency_token
```

## 🚨 Emergency Procedures

### Bypass Token
For emergency access, use the bypass token:
```bash
curl -H "X-License-Bypass: YOUR_BYPASS_TOKEN" https://yourapp.com
```

### License Revocation
```bash
curl -X POST -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"license_key":"LICENSE_KEY"}' \
  https://your-license-server.com/api/revoke
```

## 📈 Best Practices

### 1. **Regular Monitoring**
- Monitor license server logs daily
- Check for suspicious activity patterns
- Review installation statistics weekly

### 2. **Security Updates**
- Keep license server updated
- Rotate API tokens regularly
- Monitor for new attack vectors

### 3. **Client Communication**
- Provide clear license terms
- Offer support for legitimate issues
- Have emergency contact procedures

### 4. **Documentation**
- Document all license procedures
- Maintain client contact information
- Keep audit trails for legal purposes

## 🔒 Legal Protection

This system provides:
- **Evidence of unauthorized use** through detailed logging
- **Hardware fingerprinting** for legal identification
- **Installation tracking** for license violation proof
- **Audit trails** for legal proceedings

## 🆘 Support

For support and questions:
- Email: support@yourcompany.com
- Documentation: [Your Documentation URL]
- Emergency: Use bypass token for critical issues

---

**⚠️ Important**: This system is designed to protect your intellectual property while maintaining legitimate client access. Always provide support for legitimate license issues and have clear procedures for emergency situations. 