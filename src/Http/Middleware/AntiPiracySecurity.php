<?php

namespace Acecoderz\LicenseManager\Http\Middleware;

use Acecoderz\LicenseManager\AntiPiracyManager;
use Acecoderz\LicenseManager\Http\Middleware\MiddlewareHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AntiPiracySecurity
{
    protected $antiPiracyManager;

    public function __construct()
    {
        // Don't inject dependencies in constructor to avoid circular dependencies
        // We'll resolve them in the handle method
    }

    protected function getAntiPiracyManager()
    {
        if (!$this->antiPiracyManager) {
            $this->antiPiracyManager = app(AntiPiracyManager::class);
        }
        return $this->antiPiracyManager;
    }

    public function handle(Request $request, Closure $next)
    {
        // Skip validation for certain routes (if needed)
        if ($this->shouldSkipValidation($request)) {
            return $next($request);
        }

        // Check if we're in maintenance mode or have a bypass
        if ($this->hasBypass($request)) {
            return $next($request);
        }

        // Perform comprehensive anti-piracy validation
        if (!$this->getAntiPiracyManager()->validateAntiPiracy()) {
            $this->handleValidationFailure($request);
            return $this->getFailureResponse($request);
        }

        // Log successful validation (for monitoring)
        $this->logSuccessfulValidation($request);

        return $next($request);
    }

    /**
     * Check if validation should be skipped for this request
     */
    private function shouldSkipValidation(Request $request): bool
    {
        $skipRoutes = config('license-manager.skip_routes', []);
        $path = $request->path();

        // Skip specific routes
        foreach ($skipRoutes as $route) {
            $cleanRoute = trim($route, '/');
            if (str_starts_with($path, $cleanRoute)) {
                return true;
            }
        }

        // Skip file extensions (assets, images, etc.)
        $skipExtensions = ['.css', '.js', '.png', '.jpg', '.gif', '.ico', '.svg', '.woff', '.woff2'];
        foreach ($skipExtensions as $ext) {
            if (str_ends_with($path, $ext)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for bypass conditions (development, testing, etc.)
     */
    private function hasBypass(Request $request): bool
    {
        // Allow bypass in local environment (unless explicitly disabled for testing)
        if (app()->environment('local') && !config('license-manager.disable_local_bypass', false)) {
            return true;
        }

        // Check for bypass token (for emergency access)
        $bypassToken = config('license-manager.bypass_token');
        if ($bypassToken && $request->header('X-License-Bypass') === $bypassToken) {
            Log::warning('License bypass used', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Handle validation failure
     */
    private function handleValidationFailure(Request $request): void
    {
        $report = $this->getAntiPiracyManager()->getValidationReport();
        
        Log::error('Anti-piracy validation failed', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'report' => $report,
        ]);

        // Increment failure counter
        $failureKey = 'license_failures_' . $request->ip();
        $failures = Cache::get($failureKey, 0) + 1;
        Cache::put($failureKey, $failures, now()->addHours(1));

        // If too many failures, blacklist the IP temporarily
        $maxFailures = config('license-manager.validation.max_failures', 10);
        if ($failures > $maxFailures) {
            $blacklistDuration = config('license-manager.validation.blacklist_duration', 24);
            Cache::put('blacklisted_ip_' . $request->ip(), true, now()->addHours($blacklistDuration));
            Log::error('IP blacklisted due to repeated license failures', [
                'ip' => $request->ip(),
                'failures' => $failures,
            ]);
        }
    }

    /**
     * Get appropriate failure response
     */
    private function getFailureResponse(Request $request)
    {
        // Check if IP is blacklisted
        if (Cache::get('blacklisted_ip_' . $request->ip())) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP has been temporarily blocked due to repeated license violations.',
                'code' => 'IP_BLACKLISTED'
            ], 403);
        }

        // Check if it's an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'License validation failed',
                'message' => 'Invalid or unauthorized license. Please contact support.',
                'code' => 'LICENSE_INVALID'
            ], 403);
        }

        // For web requests, return a proper error page
        return response()->view('errors.license', [
            'title' => 'License Error',
            'message' => 'Your license could not be validated. Please contact support.',
            'support_email' => config('license-manager.support_email', 'support@example.com'),
        ], 403);
    }

    /**
     * Log successful validation for monitoring
     */
    private function logSuccessfulValidation(Request $request): void
    {
        // Only log occasionally to avoid spam
        $logKey = 'license_success_log_' . date('Y-m-d-H');
        $successCount = Cache::get($logKey, 0) + 1;
        Cache::put($logKey, $successCount, now()->addHour());

        // Log every Nth successful validation (configurable)
        $logInterval = config('license-manager.validation.success_log_interval', 100);
        if ($successCount % $logInterval === 0) {
            Log::info('License validation successful', [
                'success_count' => $successCount,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
        }
    }
} 