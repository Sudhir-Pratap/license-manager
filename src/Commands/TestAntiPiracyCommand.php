<?php
namespace Acecoderz\LicenseManager\Commands;

use InsuranceCore\Helpers\AntiPiracyManager;
use Illuminate\Console\Command;

class TestAntiPiracyCommand extends Command {
	protected $signature   = 'license:test-anti-piracy {--detailed}';
	protected $description = 'Test the anti-piracy system and generate a detailed report';

	public function handle(AntiPiracyManager $antiPiracyManager) {
		$this->info('🔒 Testing Anti-Piracy System...');
		$this->newLine();

		// Test basic validation
		$isValid = $antiPiracyManager->validateAntiPiracy();
		
		if ($isValid) {
			$this->info('✅ Anti-piracy validation passed');
		} else {
			$this->error('❌ Anti-piracy validation failed');
		}

		// Get detailed report
		$report = $antiPiracyManager->getValidationReport();
		
		$this->newLine();
		$this->info('📊 Installation Details:');
		$this->table(
			['Property', 'Value'],
			[
				['Installation ID', $report['installation_id']],
				['Hardware Fingerprint', substr($report['hardware_fingerprint'], 0, 16) . '...'],
				['Domain', $report['server_info']['domain']],
				['IP Address', $report['server_info']['ip']],
				['User Agent', substr($report['server_info']['user_agent'], 0, 50) . '...'],
				['Validation Time', $report['validation_time']],
			]
		);

		if ($this->option('detailed')) {
			$this->newLine();
			$this->info('🔍 Detailed Hardware Fingerprint Components:');
			
			// Get hardware components (you would need to expose this from AntiPiracyManager)
			$this->warn('Hardware fingerprint includes:');
			$this->line('• Server characteristics');
			$this->line('• File system paths');
			$this->line('• Database configuration');
			$this->line('• PHP environment');
			$this->line('• System resources');
		}

		// Test server communication
		$this->newLine();
		$this->info('🌐 Testing Server Communication...');
		
		try {
			$licenseServer = config('license-manager.license_server');
			$apiToken = config('license-manager.api_token');
			
			$response = \Illuminate\Support\Facades\Http::withHeaders([
				'Authorization' => 'Bearer ' . $apiToken,
			])->timeout(10)->get("{$licenseServer}/api/heartbeat");

			if ($response->successful()) {
				$this->info('✅ License server communication successful');
			} else {
				$this->error('❌ License server communication failed');
			}
		} catch (\Exception $e) {
			$this->error('❌ License server communication error: ' . $e->getMessage());
		}

		// Security recommendations
		$this->newLine();
		$this->info('🛡️ Security Recommendations:');
		$this->line('1. Keep your license keys secure');
		$this->line('2. Monitor installation logs regularly');
		$this->line('3. Use HTTPS for all communications');
		$this->line('4. Regularly update your license server');
		$this->line('5. Monitor for suspicious activity');

		$this->newLine();
		$this->info('✅ Anti-piracy test completed');
	}
} 
