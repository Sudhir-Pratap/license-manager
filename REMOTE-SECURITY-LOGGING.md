# Remote Security Logging Architecture

## Overview

Security logs are now sent from client applications to your license-server instead of being stored locally. This provides **complete visibility** into what's happening across all your clients while keeping sensitive security data off their servers.

## Why Remote Logging?

### 🔒 **Security Benefits**
- **No sensitive logs on client servers** - Clients can't see what security checks are failing
- **Centralized monitoring** - All security events in one place on your server
- **Pattern detection** - Identify widespread attacks across all clients
- **Audit trail** - Complete history of security events for compliance

### 📊 **Business Benefits**
- **Real-time monitoring** - See security violations as they happen
- **Client behavior analysis** - Understand what clients are doing
- **Threat detection** - Identify suspicious patterns early
- **License compliance** - Track license violations and misuse

## How It Works

### Client Side (License Manager Package)
1. **Security events detected** - File tampering, hardware changes, suspicious activity
2. **RemoteSecurityLogger** - Captures the event with context
3. **Async send** - Sends to license-server without blocking client requests
4. **Fallback cache** - If server is unreachable, logs are cached for retry

### Server Side (License Server)
1. **Receives security log** - Via `/api/report-suspicious` endpoint
2. **Stores in database** - `security_logs` table for querying/analysis
3. **Creates violation** - If suspicion score is high (≥20)
4. **Updates license** - Tracks suspicion scores and violation counts
5. **Logs locally** - Also writes to `storage/logs/security.log`

## What Gets Logged?

### Critical Security Events
- ✅ **File tampering** - Unauthorized modifications to protected files
- ✅ **Multiple domains** - License used on too many domains
- ✅ **Hardware changes** - Significant hardware fingerprint changes
- ✅ **Missing watermarks** - Copy protection watermarks removed
- ✅ **Validation failures** - License validation issues during grace period

### Log Levels
- **CRITICAL** (score: 30) - File modifications, severe tampering
- **ALERT** (score: 25) - Potentially suspicious activity patterns
- **ERROR** (score: 15) - Security check failures
- **WARNING** (score: 10) - Minor suspicious indicators

## Configuration

### Client (.env)
```env
# Enable remote security logging (default: true)
LICENSE_REMOTE_SECURITY_LOGGING=true

# Your license server
LICENSE_SERVER=http://license-server.test
LICENSE_API_TOKEN=your-api-token
```

### Server (logging.php)
The `security` log channel is configured to:
- Store logs in `storage/logs/security.log`
- Keep logs for 90 days
- Only log warning level and above

## Database Schema

### `security_logs` Table
- `license_id` - Link to license
- `client_id` - Client identifier
- `log_level` - critical, alert, error, warning
- `log_message` - Security event description
- `log_context` - JSON data with event details
- `domain`, `ip_address`, `user_agent` - Request context
- `suspicion_score` - Calculated threat score
- `logged_at` - When event occurred on client

## Querying Security Logs

### Recent Critical Events
```sql
SELECT * FROM security_logs 
WHERE log_level = 'critical' 
ORDER BY logged_at DESC 
LIMIT 50;
```

### High Suspicion Clients
```sql
SELECT client_id, COUNT(*) as event_count, SUM(suspicion_score) as total_score
FROM security_logs
WHERE logged_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY client_id
HAVING total_score > 50
ORDER BY total_score DESC;
```

### Most Common Violations
```sql
SELECT log_message, COUNT(*) as occurrences
FROM security_logs
WHERE logged_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY log_message
ORDER BY occurrences DESC;
```

## Monitoring Dashboard

Access security logs via:
- Database queries on `security_logs` table
- Laravel log files: `storage/logs/security.log`
- Admin dashboard API: `/api/admin/violations/recent`

## Performance

### Non-Blocking
- Logs sent **asynchronously** using queues (if available)
- **1-second timeout** max - won't delay client requests
- **Fire-and-forget** - Client doesn't wait for response

### Reliability
- **Failed logs cached** - Retry mechanism for critical logs
- **Automatic retry** - Package retries failed logs on next request
- **Graceful degradation** - If server is down, package continues working

## Best Practices

1. **Monitor regularly** - Check `security_logs` table weekly
2. **Set up alerts** - Email/Slack notifications for critical events
3. **Analyze patterns** - Identify common violations
4. **Respond quickly** - Revoke licenses for severe violations
5. **Review scores** - High suspicion scores indicate reselling/piracy

## Disabling Remote Logging

If you want local logs instead (not recommended):

```env
LICENSE_REMOTE_SECURITY_LOGGING=false
```

This will fall back to local `storage/logs/security.log` files on each client, which you won't have access to.

## Migration

Run the migration to create the `security_logs` table:

```bash
php artisan migrate
```

This creates the table with proper indexes for fast querying.

