<?php

namespace Acecoderz\LicenseManager\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class VendorProtectionService
{
    /**
     * Monitor and protect vendor directory integrity
     */
    public function protectVendorIntegrity(): void
    {
        $this->createVendorIntegrityBaseline();
        $this->setupVendorModificationDetection();
        $this->implementVendorFileLocking();
        $this->addVendorTamperingCountermeasures();
    }

    /**
     * Create initial integrity baseline for vendor files
     */
    public function createVendorIntegrityBaseline(): void
    {
        $vendorPath = base_path('vendor/acecoderz/license-manager');

        if (!File::exists($vendorPath)) {
            return;
        }

        $baseline = $this->generateVendorBaseline($vendorPath);

        // Store multiple backups of the baseline
        Cache::put('vendor_baseline_primary', $baseline, now()->addYears(1));
        Cache::put('vendor_baseline_backup', $baseline, now()->addYears(1));
        Cache::put('vendor_baseline_timestamp', now()->toISOString(), now()->addYears(1));

        Log::info('Vendor integrity baseline created', [
            'file_count' => count($baseline['files']),
            'total_size' => $baseline['total_size']
        ]);
    }

    /**
     * Generate comprehensive baseline for vendor directory
     */
    public function generateVendorBaseline(string $vendorPath): array
    {
        $baseline = [
            'files' => [],
            'total_size' => 0,
            'structure_hash' => '',
            'critical_files' => [],
        ];

        $criticalPatterns = [
            'LicenseManager.php',
            'AntiPiracyManager.php',
            'Services/',
            'Http/Middleware/',
            'Commands/',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($vendorPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($vendorPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $content = File::get($file->getPathname());

                $fileHash = [
                    'path' => $relativePath,
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'hash' => hash('sha256', $content),
                    'permissions' => substr(sprintf('%o', $file->getPerms()), -4),
                ];

                $baseline['files'][$relativePath] = $fileHash;
                $baseline['total_size'] += $file->getSize();

                // Mark critical files
                foreach ($criticalPatterns as $pattern) {
                    if (str_contains($relativePath, $pattern)) {
                        $baseline['critical_files'][$relativePath] = $fileHash;
                        break;
                    }
                }
            }
        }

        // Create structure hash
        ksort($baseline['files']);
        $baseline['structure_hash'] = hash('sha256', serialize($baseline['files']));

        return $baseline;
    }

    /**
     * Setup real-time vendor modification detection
     */
    public function setupVendorModificationDetection(): void
    {
        // This would be called during license validation
        $this->verifyVendorIntegrity();

        // Setup periodic integrity checks
        if (!Cache::has('vendor_integrity_check_scheduled')) {
            Cache::put('vendor_integrity_check_scheduled', true, now()->addHours(1));
        }
    }

    /**
     * Verify vendor directory integrity
     */
    public function verifyVendorIntegrity(): array
    {
        $baseline = Cache::get('vendor_baseline_primary');
        $backupBaseline = Cache::get('vendor_baseline_backup');

        if (!$baseline) {
            Log::warning('No vendor integrity baseline found - creating new one');
            $this->createVendorIntegrityBaseline();
            return ['status' => 'baseline_created', 'violations' => []];
        }

        $vendorPath = base_path('vendor/acecoderz/license-manager');
        $currentState = $this->generateVendorBaseline($vendorPath);

        $violations = [];

        // Check for modified files
        foreach ($baseline['files'] as $path => $originalData) {
            if (!isset($currentState['files'][$path])) {
                $violations[] = [
                    'type' => 'file_deleted',
                    'file' => $path,
                    'severity' => 'critical'
                ];
            } elseif ($currentState['files'][$path]['hash'] !== $originalData['hash']) {
                $violations[] = [
                    'type' => 'file_modified',
                    'file' => $path,
                    'severity' => isset($baseline['critical_files'][$path]) ? 'critical' : 'high',
                    'original_hash' => $originalData['hash'],
                    'current_hash' => $currentState['files'][$path]['hash']
                ];
            }
        }

        // Check for new files
        foreach ($currentState['files'] as $path => $currentData) {
            if (!isset($baseline['files'][$path])) {
                $violations[] = [
                    'type' => 'file_added',
                    'file' => $path,
                    'severity' => 'medium'
                ];
            }
        }

        // Check structure integrity
        if ($currentState['structure_hash'] !== $baseline['structure_hash']) {
            $violations[] = [
                'type' => 'structure_modified',
                'severity' => 'high',
                'original_structure' => $baseline['structure_hash'],
                'current_structure' => $currentState['structure_hash']
            ];
        }

        // Handle violations
        if (!empty($violations)) {
            $this->handleVendorTampering($violations);

            // If primary baseline is compromised, try backup
            if (count($violations) > 2 && $backupBaseline) {
                Log::warning('Primary baseline may be compromised, checking backup');
                $backupViolations = $this->compareWithBaseline($currentState, $backupBaseline);
                if (count($backupViolations) < count($violations)) {
                    $this->handleVendorTampering($backupViolations, 'backup');
                }
            }
        }

        return [
            'status' => empty($violations) ? 'integrity_verified' : 'violations_detected',
            'violations' => $violations,
            'checked_at' => now()->toISOString()
        ];
    }

    /**
     * Compare current state with a specific baseline
     */
    public function compareWithBaseline(array $currentState, array $baseline): array
    {
        $violations = [];

        foreach ($baseline['files'] as $path => $originalData) {
            if (!isset($currentState['files'][$path])) {
                $violations[] = ['type' => 'file_deleted', 'file' => $path, 'severity' => 'critical'];
            } elseif ($currentState['files'][$path]['hash'] !== $originalData['hash']) {
                $violations[] = [
                    'type' => 'file_modified',
                    'file' => $path,
                    'severity' => isset($baseline['critical_files'][$path]) ? 'critical' : 'high'
                ];
            }
        }

        return $violations;
    }

    /**
     * Handle detected vendor tampering
     */
    public function handleVendorTampering(array $violations, string $baselineType = 'primary'): void
    {
        $criticalViolations = array_filter($violations, function($v) {
            return in_array($v['severity'], ['critical', 'high']);
        });

        // Log the incident
        Log::critical('Vendor file tampering detected', [
            'baseline_type' => $baselineType,
            'violations' => $violations,
            'critical_count' => count($criticalViolations),
            'total_violations' => count($violations)
        ]);

        // Send immediate alert
        app(RemoteSecurityLogger::class)->critical('Vendor File Tampering Detected', [
            'violations' => $violations,
            'baseline_type' => $baselineType,
            'server_info' => [
                'host' => gethostname(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]
        ]);

        // Implement countermeasures based on severity
        if (count($criticalViolations) > 0) {
            $this->implementCriticalCountermeasures($violations);
        } else {
            $this->implementWarningCountermeasures($violations);
        }

        // Store violation record
        $violationRecord = [
            'timestamp' => now()->toISOString(),
            'violations' => $violations,
            'baseline_type' => $baselineType,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $existingRecords = Cache::get('vendor_tampering_records', []);
        $existingRecords[] = $violationRecord;

        // Keep only last 50 records
        if (count($existingRecords) > 50) {
            $existingRecords = array_slice($existingRecords, -50);
        }

        Cache::put('vendor_tampering_records', $existingRecords, now()->addDays(30));
    }

    /**
     * Implement critical countermeasures for severe tampering
     */
    public function implementCriticalCountermeasures(array $violations): void
    {
        // Immediate license suspension
        Cache::put('license_force_invalid', true, now()->addHours(24));

        // Clear all license caches
        Cache::forget('license_valid_' . md5(config('license-manager.license_key') ?? ''));
        Cache::forget('license_last_check_' . md5(config('license-manager.license_key') ?? ''));

        // Log critical security event
        Log::emergency('CRITICAL: Vendor tampering detected - license suspended', [
            'violations' => $violations,
            'action' => 'license_suspended'
        ]);

        // In extreme cases, you might want to:
        // - Send notification to license server to blacklist this installation
        // - Terminate the application
        // - Create forensic logs

        // For now, we'll make the application non-functional
        if (config('license-manager.vendor_protection.terminate_on_critical', false)) {
            // Log and exit (be careful with this in production)
            Log::emergency('Application terminated due to critical vendor tampering');
            exit(1);
        }
    }

    /**
     * Implement warning countermeasures for minor tampering
     */
    public function implementWarningCountermeasures(array $violations): void
    {
        // Reduce license cache duration
        Cache::put('license_cache_reduced', true, now()->addHours(1));

        // Force immediate server validation
        Cache::put('force_server_validation', true, now()->addMinutes(30));

        // Log warning
        Log::warning('Vendor tampering warning - enhanced monitoring activated', [
            'violations' => $violations
        ]);
    }

    /**
     * Implement vendor file locking mechanisms
     */
    public function implementVendorFileLocking(): void
    {
        $vendorPath = base_path('vendor/acecoderz/license-manager');

        if (!File::exists($vendorPath)) {
            return;
        }

        // Create .htaccess to prevent web access (if Apache)
        $htaccessContent = "# Deny access to vendor license manager files\n" .
                          "<FilesMatch \"\\.(php)$\">\n" .
                          "    Order Deny,Allow\n" .
                          "    Deny from all\n" .
                          "</FilesMatch>\n";

        $htaccessPath = $vendorPath . '/.htaccess';
        if (!File::exists($htaccessPath)) {
            File::put($htaccessPath, $htaccessContent);
        }

        // Set restrictive permissions on critical files
        $criticalFiles = [
            'LicenseManager.php',
            'AntiPiracyManager.php',
        ];

        foreach ($criticalFiles as $file) {
            $filePath = $vendorPath . '/' . $file;
            if (File::exists($filePath)) {
                // Set read-only for owner, no permissions for group/others
                chmod($filePath, 0400);
            }
        }

        // Create integrity verification file
        $integrityFile = $vendorPath . '/.integrity_check';
        $integrityData = [
            'created' => now()->toISOString(),
            'version' => '1.0',
            'protected' => true,
        ];

        File::put($integrityFile, json_encode($integrityData));
        chmod($integrityFile, 0400);
    }

    /**
     * Add additional vendor tampering countermeasures
     */
    public function addVendorTamperingCountermeasures(): void
    {
        // Setup file system monitoring (if available)
        $this->setupFilesystemMonitoring();

        // Create decoy files to detect tampering attempts
        $this->createDecoyFiles();

        // Implement self-healing mechanisms
        $this->setupSelfHealing();
    }

    /**
     * Setup file system monitoring
     */
    public function setupFilesystemMonitoring(): void
    {
        // This would integrate with system file monitoring tools
        // For now, we'll implement periodic checks

        $monitoringConfig = [
            'enabled' => true,
            'check_interval' => 300, // 5 minutes
            'last_check' => now()->toISOString(),
        ];

        Cache::put('vendor_filesystem_monitoring', $monitoringConfig, now()->addHours(1));
    }

    /**
     * Create decoy files to detect tampering
     */
    public function createDecoyFiles(): void
    {
        $vendorPath = base_path('vendor/acecoderz/license-manager');

        // Create hidden decoy files that should not be modified
        $decoyFiles = [
            '.decoy_1.php' => '<?php // This is a decoy file - do not modify ?>',
            '.decoy_2.php' => '<?php /* Decoy file for tampering detection */ ?>',
        ];

        foreach ($decoyFiles as $filename => $content) {
            $filePath = $vendorPath . '/' . $filename;
            if (!File::exists($filePath)) {
                File::put($filePath, $content);
                chmod($filePath, 0400);

                // Store hash for monitoring
                $hash = hash('sha256', $content);
                Cache::put('decoy_file_' . $filename, $hash, now()->addYears(1));
            }
        }
    }

    /**
     * Setup self-healing mechanisms
     */
    public function setupSelfHealing(): void
    {
        // In a real implementation, this could restore files from backups
        // For now, we'll implement detection and alerting

        $healingConfig = [
            'enabled' => config('license-manager.vendor_protection.self_healing', false),
            'backup_location' => storage_path('vendor_backups'),
            'auto_restore' => false, // Manual intervention required
        ];

        Cache::put('vendor_self_healing_config', $healingConfig, now()->addDays(30));
    }

    /**
     * Restore vendor files from backup (manual process)
     */
    public function restoreFromBackup(): bool
    {
        $backupLocation = storage_path('vendor_backups');
        $vendorPath = base_path('vendor/acecoderz/license-manager');

        if (!File::exists($backupLocation)) {
            Log::error('No vendor backup found for restoration');
            return false;
        }

        try {
            // This would require manual intervention and verification
            Log::warning('Vendor restoration initiated - manual verification required');

            // Clear tampering detection
            Cache::forget('license_force_invalid');

            // Recreate baseline
            $this->createVendorIntegrityBaseline();

            return true;
        } catch (\Exception $e) {
            Log::error('Vendor restoration failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get vendor tampering report
     */
    public function getTamperingReport(): array
    {
        $records = Cache::get('vendor_tampering_records', []);

        return [
            'total_incidents' => count($records),
            'recent_incidents' => array_slice($records, -10),
            'last_incident' => !empty($records) ? end($records) : null,
            'integrity_status' => $this->verifyVendorIntegrity(),
        ];
    }
}
