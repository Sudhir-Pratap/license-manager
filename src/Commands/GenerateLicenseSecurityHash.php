<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateLicenseSecurityHash extends Command {
	protected $signature   = 'license:generate-hash';
	protected $description = 'Generate SHA-256 hash for LicenseSecurity middleware';

	public function handle() {
		$filePath = base_path('vendor/acecoderz/license-manager/src/Http/Middleware/LicenseSecurity.php');

		if (! file_exists($filePath)) {
			$this->error('LicenseSecurity.php not found in vendor/acecoderz/license-manager.');
			return 1;
		}

		$hash = hash_file('sha256', $filePath);
		$this->info('LicenseSecurity middleware hash: ' . $hash);
		$this->info('Add this to your .env file:');
		$this->info('LICENSE_SECURITY_HASH=' . $hash);

		return 0;
	}
}