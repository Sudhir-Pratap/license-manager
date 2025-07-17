<?php

namespace Acecoderz\LicenseManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Acecoderz\LicenseManager\AntiPiracyManager;
use Acecoderz\LicenseManager\LicenseManager;

class DiagnoseLicenseCommand extends Command
{
    protected $signature = 'license:diagnose {--fix : Attempt to fix common issues}';
    protected $description = 'Diagnose license validation issues';

    public function handle()
    {
        $this->info('🔍 License Diagnosis Started');
        $this->newLine();

        $issues = [];
        $fixes = [];

        // Check configuration
        $this->checkConfiguration($issues, $fixes);

        // Check cache status
        $this->checkCacheStatus($issues, $fixes);

        // Check hardware fingerprint
        $this->checkHardwareFingerprint($issues, $fixes);

        // Check license validation
        $this->checkLicenseValidation($issues, $fixes);

        // Check anti-piracy validation
        $this->checkAntiPiracyValidation($issues, $fixes);

        // Display results
        $this->displayResults($issues, $fixes);

        // Apply fixes if requested
        if ($this->option('fix') && !empty($fixes)) {
            $this->applyFixes($fixes);
        }

        return empty($issues) ? 0 : 1;
    }

    private function checkConfiguration(&$issues, &$fixes)
    {
        $this->info('📋 Checking Configuration...');

        $requiredConfigs = [
            'license-manager.license_key' => 'LICENSE_KEY',
            'license-manager.product_id' => 'LICENSE_PRODUCT_ID',
            'license-manager.api_token' => 'LICENSE_API_TOKEN',
            'license-manager.license_server' => 'LICENSE_SERVER',
        ];

        foreach ($requiredConfigs as $config => $env) {
            $value = config($config);
            if (empty($value)) {
                $issues[] = "❌ Missing {$env} environment variable";
                $fixes[] = "Set {$env} in your .env file";
            } else {
                $this->line("✅ {$env}: " . substr($value, 0, 10) . '...');
            }
        }
    }

    private function checkCacheStatus(&$issues, &$fixes)
    {
        $this->info('💾 Checking Cache Status...');

        $licenseKey = config('license-manager.license_key');
        $productId = config('license-manager.product_id');
        $clientId = config('license-manager.client_id');

        if ($licenseKey && $productId && $clientId) {
            $cacheKey = "license_valid_{$licenseKey}_{$productId}_{$clientId}";
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                $this->line("✅ License cache: Valid");
            } else {
                $this->line("⚠️  License cache: Not found");
                $fixes[] = "Run: php artisan license:reset-cache";
            }
        }

        $hardwareFingerprint = Cache::get('hardware_fingerprint');
        if ($hardwareFingerprint) {
            $this->line("✅ Hardware fingerprint: Cached");
        } else {
            $this->line("⚠️  Hardware fingerprint: Not cached");
        }
    }

    private function checkHardwareFingerprint(&$issues, &$fixes)
    {
        $this->info('🖥️  Checking Hardware Fingerprint...');

        $licenseManager = app(LicenseManager::class);
        $currentFingerprint = $licenseManager->generateHardwareFingerprint();
        $storedFingerprint = Cache::get('hardware_fingerprint');

        if ($storedFingerprint) {
            $similarity = similar_text($storedFingerprint, $currentFingerprint, $percent);
            
            if ($percent < 70) {
                $issues[] = "❌ Hardware fingerprint mismatch (similarity: {$percent}%)";
                $fixes[] = "Run: php artisan license:reset-cache";
            } else {
                $this->line("✅ Hardware fingerprint: Valid (similarity: {$percent}%)");
            }
        } else {
            $this->line("ℹ️  Hardware fingerprint: Not stored (will be created on first validation)");
        }
    }

    private function checkLicenseValidation(&$issues, &$fixes)
    {
        $this->info('🔐 Checking License Validation...');

        try {
            $licenseManager = app(LicenseManager::class);
            $licenseKey = config('license-manager.license_key');
            $productId = config('license-manager.product_id');
            $clientId = config('license-manager.client_id');
            $domain = request()->getHost() ?: 'localhost';
            $ip = request()->ip() ?: '127.0.0.1';

            $isValid = $licenseManager->validateLicense($licenseKey, $productId, $domain, $ip, $clientId);
            
            if ($isValid) {
                $this->line("✅ License validation: Success");
            } else {
                $issues[] = "❌ License validation: Failed";
                $fixes[] = "Check license server connectivity and license key validity";
            }
        } catch (\Exception $e) {
            $issues[] = "❌ License validation error: " . $e->getMessage();
            $fixes[] = "Check network connectivity to license server";
        }
    }

    private function checkAntiPiracyValidation(&$issues, &$fixes)
    {
        $this->info('🛡️  Checking Anti-Piracy Validation...');

        try {
            $antiPiracyManager = app(AntiPiracyManager::class);
            $isValid = $antiPiracyManager->validateAntiPiracy();
            
            if ($isValid) {
                $this->line("✅ Anti-piracy validation: Success");
            } else {
                $issues[] = "❌ Anti-piracy validation: Failed";
                $fixes[] = "Run: php artisan license:reset-cache";
            }
        } catch (\Exception $e) {
            $issues[] = "❌ Anti-piracy validation error: " . $e->getMessage();
        }
    }

    private function displayResults($issues, $fixes)
    {
        $this->newLine();
        $this->info('📊 Diagnosis Results:');
        $this->newLine();

        if (empty($issues)) {
            $this->info('🎉 All checks passed! Your license system is working correctly.');
        } else {
            $this->error('❌ Found ' . count($issues) . ' issue(s):');
            foreach ($issues as $issue) {
                $this->line($issue);
            }

            if (!empty($fixes)) {
                $this->newLine();
                $this->warn('🔧 Suggested fixes:');
                foreach ($fixes as $fix) {
                    $this->line("• {$fix}");
                }
            }
        }
    }

    private function applyFixes($fixes)
    {
        $this->newLine();
        $this->info('🔧 Applying fixes...');

        foreach ($fixes as $fix) {
            if (str_contains($fix, 'license:reset-cache')) {
                $this->call('license:reset-cache', ['--force' => true]);
                $this->line("✅ Applied: Reset license cache");
            } else {
                $this->line("ℹ️  Manual fix required: {$fix}");
            }
        }
    }
} 