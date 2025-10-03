# 🔒 Acecoderz License Manager Package

Enterprise-grade license validation package for Laravel applications.

Protect your applications from unauthorized use while maintaining complete transparency for legitimate users.

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
composer require acecoderz/license-manager

# Publish configuration
php artisan vendor:publish --provider="Acecoderz\LicenseManager\LicenseManagerServiceProvider"

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

## 🛡️ Protection Features

**For You:**
- Advanced violation detection
- Geographic clustering analysis
- Automatic blocking of suspicious activity
- Evidence collection for legal action

**For Your Clients:**
- Transparent operation
- No interference with normal usage
- Built-in status checking
- Seamless deployment compatibility

**Professional license protection made simple!** ✨