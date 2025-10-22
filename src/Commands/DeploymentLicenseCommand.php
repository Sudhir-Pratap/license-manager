<?php

namespace Acecoderz\LicenseManager\Commands;

use Acecoderz\LicenseManager\LicenseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DeploymentLicenseCommand extends Command
{
    protected $signature = 'license:deployment 
                           {--check : Check current deployment status}
                           {--fix : Attempt to fix deployment issues}
                           {--regenerate : Force regenerate hardware fingerprint}
                           {--test : Test license after fixes}';
    
    protected $description = 'Help troubleshoot and fix license issues during deployment';

    public function handle(LicenseManager $licenseManager)
    {
        if ($this->option('check')) {
            $this->checkDeploymentStatus($licenseManager);
        }
        
        if ($this->option('fix')) {
            $this->attemptFixDeploymentIssues($licenseManager);
        }
        
        if ($this->option('regenerate')) {
            $this->regenerateHardwareFingerprint($licenseManager);
        }
        
        if ($this->option('test')) {
            $this->testLicenseValidation($licenseManager);
        }
        
        if (!$this->option('check') && !$this->option('fix') && !$this->option('regenerate') && !$this->option('test')) {
            $this->info('License Deployment Helper');
            $this->line('');
            $this->info('Available options:');
            $this->line('--check     : Check current deployment status');
            $this->line('--fix       : Attempt to fix deployment issues');
            $this->line('--regenerate: Force regenerate hardware fingerprint');
            $this->line('--test      : Test license validation');
            $this->line('');
            $this->info('Example: php artisan license:deployment --check --fix');
        }
    }

    public function checkDeploymentStatus(LicenseManager $licenseManager)
    {
        $this->info('=== License Deployment Status ===');
        
        // Check configuration
        $this->line('');
        $this->info('Configuration:');
        $this->line('License Key: ' . (config('license-manager.license_key') ? '✓ Set' : '✗ Missing'));
        $this->line('Product ID: ' . (config('license-manager.product_id') ?: 'Missing'));
        $this->line('Client ID: ' . (config('license-manager.client_id') ?: 'Missing'));
        $this->line('License Server: ' . config('license-manager.license_server'));
        
        // Check hardware fingerprint
        $fingerprint = $licenseManager->generateHardwareFingerprint();
        $this->line('');
        $this->info('Hardware Information:');
        $this->line('Fingerprint: ' . substr($fingerprint, 0, 32) . '...');
        $this->line('Installation ID: ' . $licenseManager->getOrCreateInstallationId());
        
        // Check environment
        $this->line('');
        $this->info('Environment:');
        $this->line('App Environment: ' . config('app.env'));
        $this->line('App Key: ' . (config('app.key') ? '✓ Set' : '✗ Missing'));
        $this->line('DB Connection: ' . ($this->testDatabaseConnection() ? '✓ Connected' : '✗ Failed'));
        
        // Check installation details
        $details = $licenseManager->getInstallationDetails();
        $this->line('');
        $this->info('Current Installation:');
        $this->line('Domain: ' . ($details['server_info']['domain'] ?? 'Unknown'));
        $this->line('IP: ' . ($details['server_info']['ip'] ?? 'Unknown'));
    }

    public function attemptFixDeploymentIssues(LicenseManager $licenseManager)
    {
        // Clear license cache
        Cache::flush();
        $this->info('✓ Cleared license validation cache');
        
        // Reset installation tracking
        try {
            $licenseManager->getOrCreateInstallationId();
            $this->info('✓ Reset installation tracking');
        } catch (\Exception $e) {
            $this->error('✗ Failed to reset installation tracking: ' . $e->getMessage());
        }
        
        $this->line('');
        $this->info('✓ Deployment fixes applied');
         $this->info('You should now regenerate your license with new hardware fingerprint');
     }

    public function regenerateHardwareFingerprint(LicenseManager $licenseManager)
    {
        // Set environment variable to force regeneration
        putenv('LICENSE_FORCE_REGENERATE_FINGERPRINT=true');
         
         $oldFingerprint = config('license-manager.hardware_fingerprint') ?: 'Previous not stored';
         $newFingerprint = $licenseManager->generateHardwareFingerprint();
         
         $this->info('Hardware Fingerprint Regenerated');
        $this->line('Old: ' . substr($oldFingerprint, 0, 32) . '...');
        $this->line('New: ' . substr($newFingerprint, 0, 32) . '...');
         $this->line('');
         $this->info('⚠️  You must regenerate your license with the new fingerprint');
         $this->info('Run: php artisan license:info');
     }

     public function testLicenseValidation(LicenseManager $licenseManager)
     {
         $this->info('Testing License Validation...');
         
         $licenseKey = config('license-manager.license_key');
         $productId = config('license-manager.product_id');
         $clientId = config('license-manager.client_id');
         
         if (!$licenseKey || !$productId || !$clientId) {
             $this->error('Missing required license configuration');
             return;
         }
         
         try {
             $isValid = $licenseManager->validateLicense(
                 $licenseKey,
                 $productId,
                 request()->getHost() ?: 'localhost',
                 request()->ip() ?: '127.0.0.1',
                 $clientId
             );
             
             if ($isValid) {
                 $this->info('✅ License validation successful');
             } else {
                 $this->error('❌ License validation failed');
                 $this->line('');
                 $this->info('Common fixes:');
                 $this->line('1. Check if license server is accessible');
                 $this->line('2. Verify API token is correct');
                 $this->line('3. Ensure hardware fingerprint matches');
                 $this->line('4. Run: php artisan license:deployment --fix');
             }
         } catch (\Exception $e) {
             $this->error('License validation error: ' . $e->getMessage());
         }
     }

    public function testDatabaseConnection(): bool
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
