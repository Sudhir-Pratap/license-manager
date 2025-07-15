<?php
namespace Acecoderz\LicenseManager;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LicenseManager {
	public function validateLicense(string $licenseKey, string $productId, string $domain, string $ip, string $clientId): bool {
		$licenseServer = config('license-manager.license_server');
		$apiToken      = config('license-manager.api_token');
		$cacheKey      = "license_valid_{$licenseKey}_{$productId}_{$clientId}";
		$lastCheckKey  = "license_last_check_{$licenseKey}_{$productId}_{$clientId}";

		// Generate hardware fingerprint
		$hardwareFingerprint = $this->generateHardwareFingerprint();
		$installationId = $this->getOrCreateInstallationId();

		// Generate enhanced checksum
		$checksum = hash('sha256', $licenseKey . $productId . $clientId . $hardwareFingerprint . config('app.key'));

		// Force server check every 30 minutes (reduced from 60)
		$lastCheck = Cache::get($lastCheckKey);
		if (! $lastCheck || Carbon::parse($lastCheck)->addMinutes(30)->isPast()) {
			Cache::forget($cacheKey);
		}

		// Check cache
		if (Cache::get($cacheKey)) {
			return true;
		}

		try {
			$response = Http::withHeaders([
				'Authorization' => 'Bearer ' . $apiToken,
			])->timeout(10)->post("{$licenseServer}/api/validate", [
				'license_key' => $licenseKey,
				'product_id'  => $productId,
				'domain'      => $domain,
				'ip'          => $ip,
				'client_id'   => $clientId,
				'checksum'    => $checksum,
				'hardware_fingerprint' => $hardwareFingerprint,
				'installation_id' => $installationId,
			]);

			if ($response->successful() && $response->json()['valid']) {
				Cache::put($cacheKey, true, now()->addMinutes(config('license-manager.cache_duration')));
				Cache::put($lastCheckKey, now(), now()->addDays(30));
				return true;
			}

			Log::warning('License validation failed', [
				'product_id' => $productId,
				'domain'     => $domain,
				'ip'         => $ip,
				'client_id'  => $clientId,
				'hardware_fingerprint' => $hardwareFingerprint,
				'installation_id' => $installationId,
				'error'      => $response->json()['message'] ?? 'Unknown error',
			]);
			return false;
		} catch (\Exception $e) {
			Log::error('License server error: ' . $e->getMessage(), [
				'client_id' => $clientId,
				'hardware_fingerprint' => $hardwareFingerprint,
			]);
			return Cache::get($cacheKey, false); // Fallback to cache
		}
	}

	/**
	 * Generate hardware fingerprint
	 */
	private function generateHardwareFingerprint(): string
	{
		$components = [
			'server_name' => $_SERVER['SERVER_NAME'] ?? '',
			'server_addr' => $_SERVER['SERVER_ADDR'] ?? '',
			'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
			'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
			'php_version' => PHP_VERSION,
			'os' => PHP_OS,
			'disk_free_space' => disk_free_space('/'),
			'memory_limit' => ini_get('memory_limit'),
			'max_execution_time' => ini_get('max_execution_time'),
		];

		// Add file system characteristics
		$components['app_path_hash'] = hash('sha256', base_path());
		$components['storage_path_hash'] = hash('sha256', storage_path());
		
		// Add database characteristics
		try {
			$components['db_name'] = config('database.connections.mysql.database') ?? '';
			$components['db_host'] = config('database.connections.mysql.host') ?? '';
		} catch (\Exception $e) {
			$components['db_name'] = '';
			$components['db_host'] = '';
		}

		return hash('sha256', serialize($components));
	}

	/**
	 * Get or create installation ID
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

	public function generateLicense(string $productId, string $domain, string $ip, string $expiry, string $clientId): string {
		$expiryFormatted = Carbon::parse($expiry)->format('Y-m-d H:i:s');
		$licenseString   = "{$productId}:{$domain}:{$ip}:{$expiryFormatted}:{$clientId}";
		$signature       = hash_hmac('sha256', $licenseString, config('app.key'));
		return encrypt("{$licenseString}:{$signature}");
	}

	/**
	 * Get installation details
	 */
	public function getInstallationDetails(): array
	{
		return [
			'hardware_fingerprint' => $this->generateHardwareFingerprint(),
			'installation_id' => $this->getOrCreateInstallationId(),
			'server_info' => [
				'domain' => request()->getHost(),
				'ip' => request()->server('SERVER_ADDR') ?? request()->ip(),
				'user_agent' => request()->userAgent(),
			],
		];
	}
}