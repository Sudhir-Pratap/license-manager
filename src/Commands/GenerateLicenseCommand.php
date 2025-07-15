<?php
namespace Acecoderz\LicenseManager\Commands;

use Acecoderz\LicenseManager\LicenseManager;
use Illuminate\Console\Command;

class GenerateLicenseCommand extends Command {
	protected $signature   = 'license:generate {--product-id=} {--domain=*} {--ip=*} {--expiry=1 year}';
	protected $description = 'Generate a license key for the application';

	public function handle(LicenseManager $licenseManager) {
		$productId = $this->option('product-id');
		$domain    = $this->option('domain');
		$ip        = $this->option('ip');
		$expiry    = now()->addYear()->toDateTimeString();

		$licenseKey = $licenseManager->generateLicense($productId, $domain, $ip, $expiry);

		$this->info('License Key: ' . $licenseKey);
		$this->info('Store this key in your .env file as LICENSE_KEY');
	}
}
