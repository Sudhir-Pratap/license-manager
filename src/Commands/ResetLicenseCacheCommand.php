<?php

namespace Acecoderz\LicenseManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ResetLicenseCacheCommand extends Command
{
    protected $signature = 'license:reset-cache {--force : Force reset without confirmation}';
    protected $description = 'Reset license validation cache and hardware fingerprint';

    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will reset all license cache and hardware fingerprint. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Resetting license cache...');

        // Clear all license-related cache
        $cacheKeys = [
            'hardware_fingerprint',
            'installation_id',
            'last_validation_time',
        ];

        // Add license-specific cache keys
        $licenseKey = config('license-manager.license_key');
        $productId = config('license-manager.product_id');
        $clientId = config('license-manager.client_id');

        if ($licenseKey && $productId && $clientId) {
            $cacheKeys[] = "license_valid_{$licenseKey}_{$productId}_{$clientId}";
            $cacheKeys[] = "license_last_check_{$licenseKey}_{$productId}_{$clientId}";
            $cacheKeys[] = "license_valid_{$licenseKey}_{$productId}_{$clientId}_recent_success";
        }

        // Clear cache keys
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear file hashes cache
        $criticalFiles = [
            'app/Http/Kernel.php',
            'config/app.php',
            'config/license-manager.php',
            'routes/web.php',
            'routes/agent.php',
        ];

        foreach ($criticalFiles as $file) {
            Cache::forget("file_hash_{$file}");
        }

        // Clear active installations cache
        if ($licenseKey) {
            Cache::forget('active_installations_' . $licenseKey);
        }

        // Clear IP blacklist
        $this->clearIpBlacklist();

        $this->info('License cache reset successfully!');
        $this->info('Hardware fingerprint will be regenerated on next request.');
        
        Log::info('License cache reset by command', [
            'user' => $this->getUserInfo(),
            'timestamp' => now()->toISOString(),
        ]);

        return 0;
    }

    public function clearIpBlacklist()
    {
        // Clear IP blacklist cache
        $blacklistPattern = 'blacklisted_ip_*';
        $keys = Cache::get('cache_keys', []);
        
        // This is a simplified approach - in production you might want to use Redis SCAN
        // or implement a more sophisticated cache key management
        foreach ($keys as $key) {
            if (str_starts_with($key, 'blacklisted_ip_')) {
                Cache::forget($key);
            }
        }
    }

    public function getUserInfo()
    {
        return [
            'ip' => request()->ip() ?? 'CLI',
            'user_agent' => request()->userAgent() ?? 'Artisan Command',
        ];
    }
} 
