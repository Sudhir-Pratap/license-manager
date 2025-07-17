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

		// Use the original client ID for checksum calculation (not the enhanced one)
		$originalClientId = $clientId;

		// Use LICENSE_SECRET for cryptography, fallback to APP_KEY for legacy
		$cryptoKey = env('LICENSE_SECRET', env('APP_KEY'));
		// Generate enhanced checksum
		$checksum = hash('sha256', $licenseKey . $productId . $originalClientId . $hardwareFingerprint . $cryptoKey);

		// Force server check every 30 minutes (reduced from 60)
		$lastCheck = Cache::get($lastCheckKey);
		if (! $lastCheck || Carbon::parse($lastCheck)->addMinutes(30)->isPast()) {
			Cache::forget($cacheKey);
		}

		// Check cache first
		if (Cache::get($cacheKey)) {
			return true;
		}

		try {
			// Debug log for license validation parameters
			Log::debug('License validation request', [
				'license_key' => $licenseKey,
				'product_id' => $productId,
				'domain' => $domain,
				'ip' => $ip,
				'client_id' => $originalClientId,
				'hardware_fingerprint' => $hardwareFingerprint,
				'installation_id' => $installationId,
				'checksum' => $checksum,
				'license_server' => $licenseServer,
			]);

			$response = Http::withHeaders([
				'Authorization' => 'Bearer ' . $apiToken,
			])->timeout(15)->post("{$licenseServer}/api/validate", [
				'license_key' => $licenseKey,
				'product_id'  => $productId,
				'domain'      => $domain,
				'ip'          => $ip,
				'client_id'   => $originalClientId,
				'checksum'    => $checksum,
				'hardware_fingerprint' => $hardwareFingerprint,
				'installation_id' => $installationId,
			]);

			if ($response->successful() && $response->json()['valid']) {
				Cache::put($cacheKey, true, now()->addMinutes(config('license-manager.cache_duration')));
				Cache::put($lastCheckKey, now(), now()->addDays(30));
				return true;
			}

			// If server validation fails, check if we have a recent successful cache
			$recentSuccess = Cache::get($cacheKey . '_recent_success');
			if ($recentSuccess && Carbon::parse($recentSuccess)->addHours(6)->isFuture()) {
				Log::warning('License server validation failed, using recent cache', [
					'product_id' => $productId,
					'domain' => $domain,
					'last_success' => $recentSuccess
				]);
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
				'response_status' => $response->status(),
			]);
			return false;
		} catch (\Exception $e) {
			Log::error('License server error: ' . $e->getMessage(), [
				'client_id' => $clientId,
				'hardware_fingerprint' => $hardwareFingerprint,
				'license_server' => $licenseServer,
			]);

			// Fallback to cache if server is unreachable
			$cachedResult = Cache::get($cacheKey, false);
			if ($cachedResult) {
				Log::info('Using cached license validation due to server error');
			}
			return $cachedResult;
		}
	}

	/**
	 * Generate hardware fingerprint (persisted)
	 */
	public function generateHardwareFingerprint(): string
	{
		$fingerprintFile = storage_path('app/hardware_fingerprint.id');
		if (File::exists($fingerprintFile)) {
			$fingerprint = File::get($fingerprintFile);
			if ($fingerprint && strlen($fingerprint) === 64) {
				return $fingerprint;
			}
		}
		$components = [
			'server_name' => request()->getHost(),
			'server_addr' => request()->ip(),
			'document_root' => base_path('public'),
			'server_software' => php_sapi_name(),
			'php_version' => PHP_VERSION,
			'os' => PHP_OS,
			'disk_free_space' => null,
			'memory_limit' => ini_get('memory_limit'),
			'max_execution_time' => ini_get('max_execution_time'),
		];
		$components['app_path_hash'] = hash('sha256', base_path());
		$components['storage_path_hash'] = hash('sha256', storage_path());
		try {
			$components['db_name'] = config('database.connections.mysql.database') ?? '';
			$components['db_host'] = config('database.connections.mysql.host') ?? '';
		} catch (\Exception $e) {
			$components['db_name'] = '';
			$components['db_host'] = '';
		}
		$fingerprint = hash('sha256', serialize($components));
		File::put($fingerprintFile, $fingerprint);
		return $fingerprint;
	}

	/**
	 * Get or create installation ID (persisted)
	 */
	public function getOrCreateInstallationId(): string
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

	public function generateLicense(string $productId, string $domain, string $ip, string $expiry, string $clientId, string $hardwareFingerprint, string $installationId): string {
		$expiryFormatted = Carbon::parse($expiry)->format('Y-m-d H:i:s');
		$licenseString   = "{$productId}|{$domain}|{$ip}|{$expiryFormatted}|{$clientId}|{$hardwareFingerprint}|{$installationId}";
		$cryptoKey = env('LICENSE_SECRET', env('APP_KEY'));
		$signature       = hash_hmac('sha256', $licenseString, $cryptoKey);
		return encrypt("{$licenseString}|{$signature}");
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
				'ip' => request()->ip(),
				'user_agent' => request()->userAgent(),
			],
		];
	}
}