<?php

namespace Acecoderz\LicenseManager\Http\Middleware;

use Acecoderz\LicenseManager\AntiPiracyManager;
use Acecoderz\LicenseManager\Services\CopyProtectionService;
use Acecoderz\LicenseManager\Services\WatermarkingService;
use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StealthLicenseMiddleware
{
    /**
     * Transparent license validation without client awareness
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip if stealth mode is disabled
        if (!config('license-manager.stealth.enabled', true)) {
            return $next($request);
        }

        // Perform copy protection checks first
        $copyProtectionService = app(CopyProtectionService::class);
        $isReselling = $copyProtectionService->detectResellingBehavior([
            'domain' => $request->getHost(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($isReselling && config('license-manager.stealth.silent_fail', true)) {
            // Don't block immediately - let it continue but monitor closely
            app(\Acecoderz\LicenseManager\Services\RemoteSecurityLogger::class)->warning('Copy protection triggered - monitoring', [
                'domain' => $request->getHost(),
                'ip' => $request->ip(),
            ]);
        }

        // Check if we should defer validation (for better UX)
        if (config('license-manager.stealth.deferred_enforcement', true)) {
            return $this->handleDeferredValidation($request, $next);
        }

        // Immediate background validation
        return $this->handleBackgroundValidation($request, $next);
    }

    /**
     * Handle deferred validation (validate after response is sent)
     */
    private function handleDeferredValidation(Request $request, Closure $next)
    {
        // Allow request to proceed immediately
        $response = $next($request);

        // Add watermarking for copy protection
        $this->applyWatermarking($response);

        // Schedule background validation
        $this->scheduleBackgroundValidation($request);

        return $response;
    }

    /**
     * Handle immediate background validation
     */
    private function handleBackgroundValidation(Request $request, Closure $next)
    {
        // Quick license check with minimal delay
        $cacheKey = "stealth_license_" . md5($request->getHost());
        $lastValidated = Cache::get($cacheKey);

        // Only validate every 30 minutes to minimize impact
        if ($lastValidated && $lastValidated->addMinutes(30)->isFuture()) {
            return $next($request);
        }

        // Quick validation attempt
        try {
            $antiPiracyManager = app(AntiPiracyManager::class);
            
            // Set a very short timeout for stealth mode
            $originalTimeout = config('license-manager.validation_timeout', 15);
            config(['license-manager.validation_timeout' => config('license-manager.stealth.validation_timeout', 5)]);

            $isValid = $antiPiracyManager->validateAntiPiracy();

            // Restore original timeout
            config(['license-manager.validation_timeout' => $originalTimeout]);

            // Cache result briefly
            Cache::put($cacheKey . '_valid', $isValid, now()->addMinutes(10));

            // If validation fails, check grace period
            if (!$isValid) {
                return $this->handleValidationFailure($request, $next);
            }

            // Mark as validated
            Cache::put($cacheKey, now(), 30);

        } catch (\Exception $e) {
            // Silent failure - don't bother user
            if (config('license-manager.stealth.mute_logs', true)) {
                Log::debug('Stealth license validation failed silently', [
                    'error' => $e->getMessage(),
                    'ip' => $request->ip(),
                ]);
            }

            return $this->handleValidationFailure($request, $next);
        }

        return $next($request);
    }

    /**
     * Handle validation failure gracefully
     */
    private function handleValidationFailure(Request $request, Closure $next)
    {
        // Check if we're within grace period
        if ($this->isWithinGracePeriod($request)) {
            return $next($request);
        }

        // Check if silent fail is enabled
        if (config('license-manager.stealth.silent_fail', true)) {
            // Still allow access but log for admin review
            $this->logSuspiciousActivity($request);
            return $next($request);
        }

        // In case silent mode is disabled, show minimal error
        return response()->json([
            'error' => 'Service unavailable',
            'code' => 'TEMPORARY_UNAVAILABLE'
        ], 503);
    }

    /**
     * Check if we're within grace period for offline scenarios
     */
    private function isWithinGracePeriod(Request $request): bool
    {
        $graceKey = 'license_grace_period_' . md5($request->getHost());
        $graceStart = Cache::get($graceKey);

        if (!$graceStart) {
            // First failure, start grace period
            Cache::put($graceKey, now(), config('license-manager.stealth.fallback_grace_period', 72));
            return true;
        }

        $gracePeriodHours = config('license-manager.stealth.fallback_grace_period', 72);
        return $graceStart->addHours($gracePeriodHours)->isFuture();
    }

    /**
     * Schedule background validation (fire and forget)
     */
    private function scheduleBackgroundValidation(Request $request): void
    {
        // Use queue or scheduled job if available
        try {
            // For synchronous environments, just run in background thread concept
            $cacheKey = 'pending_validation_' . md5($request->getHost());
            Cache::put($cacheKey, now()->toDateTimeString(), now()->addMinutes(5));
            
            // This would ideally trigger a background job in a real deployment
            dispatch(function () use ($request) {
                $this->performBackgroundValidation($request);
            })->onQueue('license-validation');

        } catch (\Exception $e) {
            // Queue not available, silently ignore
        }
    }

    /**
     * Perform actual background validation
     */
    private function performBackgroundValidation(Request $request): void
    {
        try {
            $antiPiracyManager = app(AntiPiracyManager::class);
            $result = $antiPiracyManager->validateAntiPiracy();

            // Only log in stealth mode for admin review
            if (config('license-manager.stealth.enabled', true)) {
                Log::channel('separate-license-log')->info('Background license validation', [
                    'valid' => $result,
                    'domain' => $request->getHost(),
                    'timestamp' => now(),
                ]);
            }

        } catch (\Exception $e) {
            if (config('license-manager.stealth.mute_logs', true)) {
                Log::channel('separate-license-log')->error('Background validation failed', [
                    'error' => $e->getMessage(),
                    'domain' => $request->getHost(),
                ]);
            }
        }
    }

    /**
     * Log suspicious activity for admin review
     */
    private function logSuspiciousActivity(Request $request): void
    {
        app(\Acecoderz\LicenseManager\Services\RemoteSecurityLogger::class)->warning('License validation failed - grace period active', [
            'domain' => $request->getHost(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now(),
            'path' => $request->path(),
        ]);
    }

    /**
     * Apply watermarking to HTML responses for copy protection
     */
    private function applyWatermarking($response): void
    {
        if (!config('license-manager.code_protection.watermarking', true)) {
            return;
        }

        // Only watermark HTML responses
        if ($response instanceof \Illuminate\Http\Response && 
            str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            
            $watermarkingService = app(WatermarkingService::class);
            $clientId = config('license-manager.client_id');
            
            $content = $response->getContent();
            
            // Apply watermarking
            $content = $watermarkingService->generateClientWatermark($clientId, $content);
            
            // Add runtime checks
            $watermarkingService->addRuntimeChecks($content);
            
            // Add anti-debug protection
            $watermarkingService->addAntiDebugProtection($content);
            
            $response->setContent($content);
        }
    }
}

