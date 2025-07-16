<?php
namespace Acecoderz\LicenseManager\Commands;

use Acecoderz\LicenseManager\LicenseManager;
use Illuminate\Console\Command;

class GenerateLicenseCommand extends Command {
    protected $signature   = 'license:generate {--product-id=} {--domain=*} {--ip=*} {--expiry=1 year} {--client-id=}';
    protected $description = 'Generate a license key for the application';

    public function handle(LicenseManager $licenseManager) {
        $productId = $this->option('product-id');
        $domain    = $this->option('domain');
        $ip        = $this->option('ip');
        $expiry    = $this->option('expiry') ?? now()->addYear()->toDateTimeString();
        $clientId  = $this->option('client-id') ?? 'default_client';

        // Support multiple domains and IPs
        if (is_array($domain)) {
            $domain = implode(',', $domain);
        }
        if (is_array($ip)) {
            $ip = implode(',', $ip);
        }

        $licenseKey = $licenseManager->generateLicense($productId, $domain, $ip, $expiry, $clientId);

        $this->info('License Key: ' . $licenseKey);
        $this->info('Store this key in your .env file as LICENSE_KEY');
    }
}
