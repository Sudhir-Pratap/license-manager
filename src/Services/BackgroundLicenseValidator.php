<?php

namespace Acecoderz\LicenseManager\Services;

use Acecoderz\LicenseManager\AntiPiracyManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BackgroundLicenseValidator
{
    private $antiPiracyManager;

    public function __construct(AntiPiracyManager $antiPiracyManager)
    {
        $this->antiPiracyManager = $antiPiracyManager;
    }

    /**
     * Validate license in background without affecting user experience
     */
    public function validateInBackground(array $context = []): bool
    {
        try {
            // Quick server health check first
            if (!$this->quickHealthCheck()) {
                return $this->handleOfflineMode($context);
            }

            // Perform validation with shorter timeout
            $timeout = config('license-manager.stealth.validation_timeout', 5);
            $originalTimeout = config('license-manager.validation_timeout', 15);
            
            // Temporarily reduce timeout for background validation
            config(['license-manager.validation_timeout' => $timeout]);
            
            $isValid = $this->antiPiracyManager->validateAntiPiracy();
            
            // Restore original timeout
            config(['license-manager.validation_timeout' => $originalTimeout]);

            // Cache result for immediate future requests
            $this->cacheValidationResult($isValid, $context);

            // Log validation result (separate log channel for stealth)
            if (config('license-manager.stealth.enabled', true)) {
                $this->logValidationResult($isValid, $context);
            }

            return $isValid;

        } catch (\Exception $e) {
            return $this->handleOfflineMode($context, $e->getMessage());
        }
    }

    /**
     * Quick health check of license server
     */
    private function quickHealthCheck(): bool
    {
        try {
            $licenseServer = config('license-manager.license_server');
            $response = Http::timeout(3)->get("{$licenseServer}/api/heartbeat");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Handle offline mode gracefully
     */
    private function handleOfflineMode(array $context, string $error = ''): bool
    {
        // Check if we're within grace period
        $gracePeriodHours = config('license-manager.stealth.fallback_grace_period', 72);
        $domainKey = md5(request()->getHost() ?? 'unknown');
        $graceKey = "grace_period_{$domainKey}";

        $graceStart = Cache::get($graceKey);
        
        if (!$graceStart) {
            // Start grace period
            Cache::put($graceKey, now(), now()->addHours($gracePeriodHours + 1));
            $graceStart = now();
        }

        $graceEnd = Carbon::parse($graceStart)->addHours($gracePeriodHours);
        $isWithinGrace = now()->isBefore($graceEnd);

        if ($isWithinGrace && config('license-manager.stealth.silent_fail', true)) {
            // Log grace period usage
            Log::channel('license')->info('License server offline - grace period active', [
                'domain' => request()->getHost(),
                'grace_end' => $graceEnd->toDateTimeString(),
                'error' => $error,
                'context' => $context,
            ]);
            
            return true; // Allow access during grace period
        }

        return false;
    }

    /**
     * Cache validation result for quick access
     */
    private function cacheValidationResult(bool $isValid, array $context): void
    {
        $domainKey = md5(request()->getHost() ?? 'unknown');
        $cacheKey = "bg_validation_{$domainKey}";
        
        Cache::put($cacheKey, [
            'valid' => $isValid,
            'timestamp' => now(),
            'context' => $context,
        ], now()->addMinutes(10));
    }

    /**
     * Log validation result separately from main application logs
     */
    private function logValidationResult(bool $isValid, array $context): void
    {
        $logData = [
            'valid' => $isValid,
            'domain' => request()->getHost(),
            'timestamp' => now(),
            'context' => $context,
            'background_validation' => true,
        ];

        if ($isValid) {
            Log::channel('license')->info('Background license validation successful', $logData);
        } else {
            Log::channel('license')->warning('Background license validation failed', $logData);
        }
    }

    /**
     * Check cached validation result
     */
    public function getCachedValidation(): ?array
    {
        $domainKey = md5(request()->getHost() ?? 'unknown');
        $cacheKey = "bg_validation_{$domainKey}";
        
        return Cache::get($cacheKey);
    }

    /**
     * Schedule periodic validation (for job queues)
     */
    public function schedulePeriodicValidation(string $domain, string $fingerprint, string $installationId): void
    {
        try {
            // This would be called by a scheduled job
            dispatch(function () use ($domain, $fingerprint, $installationId) {
                $this->performPeriodicCheck($domain, $fingerprint, $installationId);
            })->onQueue('license-validation');
            
        } catch (\Exception $e) {
            Log::channel('license')->error('Failed to schedule license validation', [
                'error' => $e->getMessage(),
                'domain' => $domain,
            ]);
        }
    }

    /**
     * Perform actual periodic validation check
     */
    private function performPeriodicCheck(string $domain, string $fingerprint, string $installationId): void
    {
        try {
            $licenseServer = config('license-manager.license_server');
            $apiToken = config('license-manager.api_token');
            $licenseKey = config('license-manager.license_key');
            $productId = config('license-manager.product_id');
            $clientId = config('license-manager.client_id');

            // Create a minimal request context
            $requestData = [
                'license_key' => $licenseKey,
                'product_id' => $productId,
                'domain' => $domain,
                'ip' => request()->ip() ?? '127.0.0.1',
                'client_id' => $clientId,
                'hardware_fingerprint' => $fingerprint,
                'installation_id' => $installationId,
                'checksum' => hash('sha256', $licenseKey . $productId . $clientId . $fingerprint . env('LICENSE_SECRET', env('APP_KEY'))),
            ];

            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => 'Bearer ' . $apiToken])
                ->post("{$licenseServer}/api/validate", $requestData);

            $logData = [
                'domain' => $domain,
                'status' => $response->status(),
                'valid' => $response->json('valid'),
                'timestamp' => now(),
                'periodic_check' => true,
            ];

            if ($response->successful() && $response->json('valid')) {
                Log::channel('license')->info('Periodic license validation successful', $logData);
                
                // Update cache
                $this->cacheValidationResult(true, ['periodic' => true]);
            } else {
                Log::channel('license')->warning('Periodic license validation failed', $logData);
                
                // Update cache
                $this->cacheValidationResult(false, ['periodic' => true]);
            }

        } catch (\Exception $e) {
            Log::channel('license')->error('Periodic license validation error', [
                'domain' => $domain,
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);
        }
    }
}
