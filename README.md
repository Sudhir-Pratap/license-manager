# 🛠️ Insurance Core Helpers Package

Utility helpers package for Laravel applications.

A comprehensive collection of utility helpers and tools for Laravel applications, providing essential functionality for development and deployment.

## 🎯 Key Features

✅ **Seamless Integration**: Zero disruption to legitimate users  
✅ **Advanced Protection**: Multi-layered anti-piracy detection  
✅ **Stealth Operation**: Invisible protection for production  
✅ **No Dependencies**: Self-contained with file-based storage  
✅ **Client-Friendly**: Built-in status checking and diagnostics  
✅ **Deployment Safe**: Automatic handling of hosting environment changes  
✅ **Legal Evidence**: Comprehensive violation tracking and reporting  

## 🚀 Quick Installation

```bash
# Install package
composer require insurance-core/helpers

# Publish configuration
php artisan vendor:publish --provider="InsuranceCore\Helpers\HelperServiceProvider"

# Configure stealth mode
php artisan license:stealth-install --config

# Check client status  
php artisan license:client-status --check
```

## 📝 Configuration

Add to your `.env` file:
```env
LICENSE_KEY=your_generated_license_key
LICENSE_SERVER=http://your-license-server.com/api
API_TOKEN=your_secure_api_token
```

## 🔧 Management Commands

- `license:client-status` - Check system status (client-friendly)
- `license:stealth-install` - Configure stealth mode
- `license:deployment-license` - Diagnose deployment issues
- `license:vendor-protect` - Manage vendor directory protection
- `license:security-audit` - Comprehensive security assessment

## 🛡️ Protection Features

**For You:**
- Advanced violation detection
- Geographic clustering analysis
- Automatic blocking of suspicious activity
- Evidence collection for legal action
- **Vendor file tampering protection**
- **Real-time integrity monitoring**
- **Automatic license suspension on tampering**

**For Your Clients:**
- Transparent operation
- No interference with normal usage
- Built-in status checking
- Seamless deployment compatibility

## 🔒 Vendor Directory Protection

**Critical Security Feature:** Automatic detection and response to vendor file modifications.

### Setup Vendor Protection
```bash
# Initialize vendor protection (run after installation)
php artisan license:vendor-protect --setup

# Verify vendor integrity
php artisan license:vendor-protect --verify

# Generate tampering report
php artisan license:vendor-protect --report
```

### What Happens When Files Are Modified

1. **Immediate Detection:** Every license validation checks vendor file integrity
2. **Automatic Response:**
   - **Minor tampering:** Enhanced monitoring and warnings
   - **Critical tampering:** Immediate license suspension
   - **Severe violations:** Application termination (optional)
3. **Remote Alerting:** Security team notified instantly
4. **Evidence Collection:** Detailed logs for legal action

### Protection Features
- **Integrity Baselines:** SHA-256 hashes of all vendor files
- **File Locking:** Restrictive permissions on critical files
- **Decoy Files:** Hidden files to detect tampering attempts
- **Real-time Monitoring:** Continuous integrity verification
- **Backup Baselines:** Multiple integrity checkpoints
- **Self-Healing:** Automated restoration capabilities

### Security Response Levels
- **Warning:** Enhanced monitoring, email alerts
- **Critical:** License suspended for 24 hours, immediate alerts
- **Severe:** Application termination, full security lockdown

**Professional license protection made simple!** ✨