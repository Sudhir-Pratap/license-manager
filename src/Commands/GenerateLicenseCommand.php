<?php
namespace InsuranceCore\Helpers\Commands;

use InsuranceCore\Helpers\LicenseManager;
use Illuminate\Console\Command;

class GenerateLicenseCommand extends Command {
    protected $signature   = 'helpers:generate {--product-id=} {--domain=*} {--ip=*} {--expiry=1 year} {--client-id=} {--hardware-fingerprint=} {--installation-id=}';
    protected $description = 'Generate a helper key for the application';

    public function handle(LicenseManager $licenseManager) {
        $productId = $this->option('product-id');
        $domain    = $this->option('domain');
        $ip        = $this->option('ip');
        $expiry    = $this->option('expiry') ?? now()->addYear()->toDateTimeString();
        $clientId  = $this->option('client-id') ?? 'default_client';
        $hardwareFingerprint = $this->option('hardware-fingerprint');
        $installationId = $this->option('installation-id');

        if (!$hardwareFingerprint || !$installationId) {
            $this->error('You must provide both --hardware-fingerprint and --installation-id. Run php artisan license:info to get these values.');
            return 1;
        }

        // Support multiple domains and IPs
        if (is_array($domain)) {
            $domain = implode(',', $domain);
        }
        if (is_array($ip)) {
            $ip = implode(',', $ip);
        }

        $licenseKey = $licenseManager->generateLicense($productId, $domain, $ip, $expiry, $clientId, $hardwareFingerprint, $installationId);

        $this->info('Helper Key: ' . $licenseKey);
        $this->info('Store this key in your .env file as HELPER_KEY');
    }
}

