<?php

namespace Acecoderz\LicenseManager;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AntiPiracyManager
{
    private $licenseManager;
    private $hardwareFingerprint;
    private $installationId;
    private $lastValidationTime;
    
    public function __construct(LicenseManager $licenseManager)
    {
        $this->licenseManager = $licenseManager;
        $this->hardwareFingerprint = $this->licenseManager->generateHardwareFingerprint();
        $this->installationId = $this->getOrCreateInstallationId();
    }

    /**
     * Comprehensive anti-piracy validation
     */
    public function validateAntiPiracy(): bool
    {
        // Multiple validation layers
        $validations = [
            'license' => $this->validateLicense(),
            'hardware' => $this->validateHardwareFingerprint(),
            'installation' => $this->validateInstallationId(),
            'tampering' => $this->detectTampering(),
            'environment' => $this->validateEnvironment(),
            'usage_patterns' => $this->validateUsagePatterns(),
            'server_communication' => $this->validateServerCommunication(),
        ];

        // Log validation results
        Log::info('Anti-piracy validation results', $validations);

        // More lenient validation - allow some failures but require critical ones to pass
        $criticalValidations = [
            'license' => $validations['license'],
            'installation' => $validations['installation'],
            'tampering' => $validations['tampering'],
        ];

        // All critical validations must pass
        if (in_array(false, $criticalValidations, true)) {
            return false;
        }

        // For non-critical validations, allow some failures but log them
        $nonCriticalFailures = 0;
        foreach ($validations as $key => $result) {
            if (!in_array($key, ['license', 'installation', 'tampering']) && !$result) {
                $nonCriticalFailures++;
            }
        }

        // Allow up to 2 non-critical failures
        if ($nonCriticalFailures > 2) {
            Log::warning('Too many non-critical validation failures', [
                'failures' => $nonCriticalFailures,
                'validations' => $validations
            ]);
            return false;
        }

        return true;
    }

    /**
     * Generate unique hardware fingerprint
     */
    private function generateHardwareFingerprint(): string
    {
        // Use the persisted hardware fingerprint from LicenseManager
        return $this->licenseManager->generateHardwareFingerprint();
    }

    /**
     * Get or create unique installation ID
     */
    private function getOrCreateInstallationId(): string
    {
        $idFile = storage_path('app/installation.id');
        
        if (File::exists($idFile)) {
            $id = File::get($idFile);
            if (Str::isUuid($id)) {
                return $id;
            }
        }

        $id = Str::uuid()->toString();
        File::put($idFile, $id);
        
        return $id;
    }

    /**
     * Validate license with enhanced security
     */
    private function validateLicense(): bool
    {
        $licenseKey = config('license-manager.license_key');
        $productId = config('license-manager.product_id');
        $clientId = config('license-manager.client_id');
        $currentDomain = request()->getHost();
        $currentIp = request()->ip();

        		// Use the original client ID for validation (not enhanced with hardware fingerprint)
		// The hardware fingerprint is sent separately to the license server
		
		return $this->licenseManager->validateLicense(
			$licenseKey, 
			$productId, 
			$currentDomain, 
			$currentIp, 
			$clientId
		);
    }

    /**
     * Validate hardware fingerprint hasn't changed
     */
    private function validateHardwareFingerprint(): bool
    {
        $storedFingerprint = Cache::get('hardware_fingerprint');
        
        if (!$storedFingerprint) {
            Cache::put('hardware_fingerprint', $this->hardwareFingerprint, now()->addDays(30));
            return true;
        }

        // Allow small variations (up to 20% difference)
        $similarity = similar_text($storedFingerprint, $this->hardwareFingerprint, $percent);
        
        // More lenient threshold - allow up to 30% difference instead of 80%
        if ($percent < 70) {
            Log::warning('Hardware fingerprint changed significantly', [
                'stored' => $storedFingerprint,
                'current' => $this->hardwareFingerprint,
                'similarity' => $percent
            ]);
            
            // If this is a significant change, update the stored fingerprint
            // This allows for legitimate hardware changes (server migration, etc.)
            if ($percent > 50) { // Still reasonable similarity
                Log::info('Updating hardware fingerprint due to significant but acceptable change', [
                    'old_similarity' => $percent,
                    'new_fingerprint' => $this->hardwareFingerprint
                ]);
                Cache::put('hardware_fingerprint', $this->hardwareFingerprint, now()->addDays(30));
                return true;
            }
            
            return false;
        }

        return true;
    }

    /**
     * Validate installation ID
     */
    private function validateInstallationId(): bool
    {
        $storedId = Cache::get('installation_id');
        
        if (!$storedId) {
            Cache::put('installation_id', $this->installationId, now()->addDays(30));
            return true;
        }

        return $storedId === $this->installationId;
    }

    /**
     * Detect code tampering
     */
    private function detectTampering(): bool
    {
        // Check for modified core files
        $criticalFiles = [
            'app/Http/Kernel.php',
            'config/app.php',
            'config/license-manager.php',
            'routes/web.php',
            'routes/agent.php',
        ];

        foreach ($criticalFiles as $file) {
            $filePath = base_path($file);
            if (File::exists($filePath)) {
                $currentHash = hash_file('sha256', $filePath);
                $storedHash = Cache::get("file_hash_{$file}");
                
                if (!$storedHash) {
                    Cache::put("file_hash_{$file}", $currentHash, now()->addDays(30));
                } elseif ($storedHash !== $currentHash) {
                    Log::error('File tampering detected', ['file' => $file]);
                    return false;
                }
            }
        }

        // Check for license middleware bypass attempts
        $middlewareStack = app('router')->getMiddleware();
        if (!isset($middlewareStack['license'])) {
            Log::error('License middleware removed from stack');
            return false;
        }

        return true;
    }

    /**
     * Validate environment consistency
     */
    private function validateEnvironment(): bool
    {
        $checks = [
            'app_key_exists' => !empty(config('app.key')),
            'license_config_exists' => !empty(config('license-manager.license_key')),
            'database_connected' => $this->testDatabaseConnection(),
            'storage_writable' => is_writable(storage_path()),
            'cache_working' => $this->testCacheConnection(),
        ];

        return !in_array(false, $checks, true);
    }

    /**
     * Validate usage patterns for suspicious activity
     */
    private function validateUsagePatterns(): bool
    {
        $currentTime = now();
        $lastValidation = Cache::get('last_validation_time');
        
        // Check for too frequent validations (potential automation)
        if ($lastValidation) {
            $timeDiff = $currentTime->diffInSeconds($lastValidation);
            if ($timeDiff < 5) { // Less than 5 seconds between validations
                Log::warning('Suspicious validation frequency detected');
                return false;
            }
        }

        Cache::put('last_validation_time', $currentTime, now()->addMinutes(10));
        
        // Check for multiple installations with same license
        $activeInstallations = Cache::get('active_installations_' . config('license-manager.license_key'), []);
        $currentInstallation = $this->installationId;
        
        if (!in_array($currentInstallation, $activeInstallations)) {
            $activeInstallations[] = $currentInstallation;
            Cache::put('active_installations_' . config('license-manager.license_key'), $activeInstallations, now()->addHours(1));
        }

        // Allow maximum 2 installations per license
        if (count($activeInstallations) > 2) {
            Log::error('Multiple installations detected for same license');
            return false;
        }

        return true;
    }

    /**
     * Validate server communication
     */
    private function validateServerCommunication(): bool
    {
        $licenseServer = config('license-manager.license_server');
        $apiToken = config('license-manager.api_token');

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
            ])->timeout(10)->get("{$licenseServer}/api/heartbeat");

            if (!$response->successful()) {
                Log::error('License server communication failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('License server communication error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test database connection
     */
    private function testDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Test cache connection
     */
    private function testCacheConnection(): bool
    {
        try {
            Cache::put('test_key', 'test_value', 1);
            $value = Cache::get('test_key');
            return $value === 'test_value';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get detailed validation report
     */
    public function getValidationReport(): array
    {
        return [
            'hardware_fingerprint' => $this->hardwareFingerprint,
            'installation_id' => $this->installationId,
            'license_key' => config('license-manager.license_key'),
            'product_id' => config('license-manager.product_id'),
            'client_id' => config('license-manager.client_id'),
            'server_info' => [
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
            'validation_time' => now()->toISOString(),
        ];
    }

    /**
     * Force immediate server validation (bypass cache)
     */
    public function forceServerValidation(): bool
    {
        Cache::forget('license_valid_' . config('license-manager.license_key') . '_' . config('license-manager.product_id') . '_' . config('license-manager.client_id'));
        return $this->validateAntiPiracy();
    }
} 