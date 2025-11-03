# 🔒 License Manager Security Checklist

## Pre-Deployment Security Measures

### 1. Environment Configuration
- [ ] Set `APP_ENV=production` in production
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure strong `APP_KEY` (32 characters minimum)
- [ ] Set `LICENSE_STEALTH_MODE=true` for transparent operation
- [ ] Configure `LICENSE_SERVER` to your secure license server
- [ ] Set `LICENSE_API_TOKEN` with secure API token
- [ ] Enable `LICENSE_ENCRYPT_CONFIG=true` for sensitive data encryption

### 2. Code Protection
- [ ] Run `php artisan license:security-audit --fix` before deployment
- [ ] Enable `LICENSE_OBFUSCATE=true` for code obfuscation
- [ ] Set `LICENSE_WATERMARK=true` for invisible watermarks
- [ ] Enable `LICENSE_ANTI_DEBUG=true` for anti-debugging measures
- [ ] Configure `LICENSE_INTEGRITY_CHECK_INTERVAL=3600` for regular checks

### 3. File System Security
- [ ] Set restrictive permissions on `.env` file (0600)
- [ ] Secure storage directories (logs: 0750, app: 0700)
- [ ] Remove development files (`.git/`, `.env.example`, `phpunit.xml`)
- [ ] Remove `composer.lock` for security (keep `composer.json`)
- [ ] Enable file integrity monitoring

### 4. PHP Security Hardening
- [ ] Disable dangerous functions: `exec`, `shell_exec`, `system`, `eval`
- [ ] Set `expose_php=Off` in php.ini
- [ ] Configure `disable_functions` in php.ini
- [ ] Set `allow_url_fopen=Off` and `allow_url_include=Off`
- [ ] Enable `open_basedir` restriction

### 5. Vendor Directory Protection
- [ ] Run `php artisan license:vendor-protect --setup` after installation
- [ ] Verify vendor integrity: `php artisan license:vendor-protect --verify`
- [ ] Set `LICENSE_VENDOR_PROTECTION=true` in environment
- [ ] Enable `LICENSE_TERMINATE_ON_CRITICAL=false` (or true for strict security)
- [ ] Configure vendor protection monitoring interval
- [ ] Setup automated vendor integrity checks

### 6. Web Server Security
- [ ] Configure HTTPS enforcement (HSTS headers)
- [ ] Set security headers (CSP, X-Frame-Options, X-Content-Type-Options)
- [ ] Disable directory listing
- [ ] Configure proper log rotation
- [ ] Set up fail2ban for brute force protection

### 7. Database Security
- [ ] Use strong database passwords
- [ ] Enable database connection encryption (SSL/TLS)
- [ ] Implement database query logging
- [ ] Set up database backup encryption
- [ ] Configure connection pooling limits

### 7. Monitoring & Alerting
- [ ] Set `LICENSE_EMAIL_ALERTS=true` for email notifications
- [ ] Configure `LICENSE_ALERT_EMAIL=security@yourcompany.com`
- [ ] Enable `LICENSE_REMOTE_ALERTS=true` for central monitoring
- [ ] Set `LICENSE_ALERT_THRESHOLD=5` for violation alerts
- [ ] Schedule regular security audits

## Deployment Commands

```bash
# Run security audit and auto-fix issues
php artisan license:security-audit --fix

# Generate detailed security report
php artisan license:security-audit --report

# Run stealth installation
php artisan license:stealth-install --config

# Check client license status
php artisan license:client-status --check
```

## Post-Deployment Verification

### Automated Checks
```bash
# Run comprehensive security audit
php artisan license:security-audit

# Monitor security events
php artisan license:security-audit --monitor

# Check license validation
php artisan license:client-status --check
```

### Manual Verification
- [ ] Verify HTTPS is enforced
- [ ] Check that debug mode is disabled
- [ ] Confirm dangerous PHP functions are disabled
- [ ] Test license validation endpoints
- [ ] Verify security headers are present
- [ ] Check file permissions are correct
- [ ] Confirm development files are removed

## Security Monitoring

### Regular Maintenance
- Run daily security audits
- Monitor license violation logs
- Review security alert emails
- Check file integrity regularly
- Update dependencies regularly
- Monitor server resource usage

### Incident Response
1. **Immediate Actions:**
   - Isolate affected systems
   - Preserve evidence and logs
   - Notify security team
   - Implement temporary blocks

2. **Investigation:**
   - Review security logs
   - Analyze attack patterns
   - Check for compromised credentials
   - Assess damage extent

3. **Recovery:**
   - Apply security patches
   - Regenerate compromised keys
   - Restore from clean backups
   - Update security configurations

## Environment Variables Reference

```env
# Application Security
APP_ENV=production
APP_DEBUG=false
APP_KEY=your_32_char_app_key_here

# License Manager Configuration
LICENSE_STEALTH_MODE=true
LICENSE_SERVER=https://your-license-server.com/api
LICENSE_API_TOKEN=your_secure_api_token
LICENSE_ENCRYPT_CONFIG=true

# Code Protection
LICENSE_OBFUSCATE=true
LICENSE_WATERMARK=true
LICENSE_ANTI_DEBUG=true
LICENSE_INTEGRITY_CHECK_INTERVAL=3600

# Security Monitoring
LICENSE_EMAIL_ALERTS=true
LICENSE_ALERT_EMAIL=security@yourcompany.com
LICENSE_REMOTE_ALERTS=true
LICENSE_ALERT_THRESHOLD=5

# Deployment Security
LICENSE_AUTO_SECURE_DEPLOYMENT=true
LICENSE_REMOVE_DEV_FILES=true
LICENSE_ENCRYPT_CONFIG=true
LICENSE_HARDEN_PHP=true

# Environment Hardening
LICENSE_PRODUCTION_ONLY=true
LICENSE_DISABLE_DEBUG_TOOLS=true
LICENSE_RESTRICT_FUNCTIONS=true
LICENSE_ENFORCE_HTTPS=true
LICENSE_DISABLE_ERROR_DISPLAY=true

# Vendor Protection
LICENSE_VENDOR_PROTECTION=true
LICENSE_VENDOR_INTEGRITY_CHECKS=true
LICENSE_VENDOR_FILE_LOCKING=true
LICENSE_VENDOR_DECOY_FILES=true
LICENSE_TERMINATE_ON_CRITICAL=false
LICENSE_VENDOR_SELF_HEALING=false
LICENSE_VENDOR_BACKUP=true
LICENSE_VENDOR_MONITOR_INTERVAL=300
```

## Security Best Practices

### For Developers
- Never commit sensitive data to version control
- Use environment variables for secrets
- Implement proper input validation
- Follow principle of least privilege
- Regular security training

### For System Administrators
- Implement network segmentation
- Use firewalls and intrusion detection
- Regular backup verification
- Monitor system logs
- Apply security patches promptly

### For License Server Operators
- Implement rate limiting
- Use HTTPS with valid certificates
- Implement proper authentication
- Log all license operations
- Regular security audits

## Emergency Contacts

- Security Team: security@yourcompany.com
- Development Team: dev@yourcompany.com
- Infrastructure Team: infra@yourcompany.com
- Legal Team: legal@yourcompany.com

## Security Incident Report Template

**Incident Summary:**
- Date/Time of incident:
- Affected systems:
- Impact assessment:
- Actions taken:
- Prevention measures:

**Technical Details:**
- Attack vector:
- Exploited vulnerability:
- Compromised data:
- System logs:
- Forensic evidence:

**Resolution:**
- Immediate containment:
- Investigation findings:
- Long-term fixes:
- Lessons learned:
