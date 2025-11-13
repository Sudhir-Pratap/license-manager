<?php

namespace Acecoderz\LicenseManager\Commands;

use InsuranceCore\Validator\LicenseManager;
use Illuminate\Console\Command;

class LicenseInfoCommand extends Command
{
    protected $signature = 'license:info';
    protected $description = 'Show the current hardware fingerprint and installation ID for license generation.';

    public function handle()
    {
        $manager = app(LicenseManager::class);
        $fingerprint = $manager->generateHardwareFingerprint();
        $currentIp = request()->ip() ?? '127.0.0.1';
        $installationId = $manager->getOrCreateInstallationId();
        
        $this->info('License Information:');
        $this->line('');
        $this->info('Hardware Fingerprint: ' . $fingerprint);
        $this->info('Installation ID: ' . $installationId);
        $this->info('Current IP: ' . $currentIp);
        
        // Show current configuration
        $this->line('');
        $this->info('Current Configuration:');
        $this->info('License Key: ' . (config('license-manager.license_key') ? 'Configured' : 'Not set'));
        $this->info('Product ID: ' . (config('license-manager.product_id') ?: 'Not set'));
        $this->info('Client ID: ' . (config('license-manager.client_id') ?: 'Not set'));
        $this->info('License Server: ' . config('license-manager.license_server'));
        
        $this->line('');
        $this->info('Use this information to generate a license:');
        $this->info('php artisan license:generate --product-id=YOUR_PRODUCT_ID --domain=' . request()->getHost() . ' --ip=' . $currentIp . ' --client-id=YOUR_CLIENT_ID');
    }
}
