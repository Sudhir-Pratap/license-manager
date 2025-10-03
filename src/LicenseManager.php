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
			// Enhanced debug log for deployment debugging
			Log::info('License validation request', [
				'license_key' => substr($licenseKey, 0, 20) . '...', // Partial key for security
				'product_id' => $productId,
				'domain' => $domain,
				'ip' => $ip,
				'client_id' => $originalClientId,
				'hardware_fingerprint' => substr($hardwareFingerprint, 0, 16) . '...',
				'installation_id' => $installationId,
				'checksum' => substr($checksum, 0, 16) . '...',
				'license_server' => $licenseServer,
				'environment' => config('app.env'),
				'deployment_context' => request()->header('X-Deployment-Context'),
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
	 * Generate hardware fingerprint (deployment-safe)
	 */
	public function generateHardwareFingerprint(): string
	{
		$fingerprintFile = storage_path('app/hardware_fingerprint.id');
		
		// Check if fingerprint exists and force regeneration if in deployment mode
		$forceRegenerate = env('LICENSE_FORCE_REGENERATE_FINGERPRINT', false);
		
		if (!$forceRegenerate && File::exists($fingerprintFile)) {
			$fingerprint = File::get($fingerprintFile);
			if ($fingerprint && strlen($fingerprint) === 64) {
				return $fingerprint;
			}
		}
		
		// Use more stable components for deployment environments
		$components = [
			// Core server identity (more stable)
			'app_key_hash' => hash('sha256', config('app.key')), // Laravel app key
			'app_name' => config('app.name'), // App name
			'app_env' => config('app.env'), // Environment
			
			// System identity (stabilized for deployment)
			'server_software' => php_sapi_name(),
			'php_version' => PHP_VERSION,
			'os_family' => PHP_OS_FAMILY ?? PHP_OS, // More stable OS identifier
			
			// Database identity (stable connection fingerprint)
			'db_connection_hash' => $this->getDatabaseConnectionFingerprint(),
			
			// File system identity (relative paths, not absolute)
			'app_signature' => $this->getApplicationSignature(),
		];
		
		// Add domain-specific component if available
		if (config('license-manager.bind_to_domain_only', false)) {
			$components['domain_bind'] = $this->getStableDomainIdentifier();
		}
		
		$fingerprint = hash('sha256', serialize($components));
		
		// Log fingerprint generation for debugging
		Log::info('Hardware fingerprint generated', [
			'components' => array_keys($components),
			'fingerprint' => $fingerprint,
			'force_regenerate' => $forceRegenerate,
			'environment' => config('app.env'),
		]);
		
		File::put($fingerprintFile, $fingerprint);
		return $fingerprint;
	}

	/**
	 * Get or create installation ID (database-persisted for deployment safety)
	 */
	public function getOrCreateInstallationId(): string
	{
		// Try to get from config first (for deployment environments)
		$configId = config('license-manager.installation_id');
		if ($configId && Str::isUuid($configId)) {
			return $configId;
		}
		
		// Try file-based storage (no database dependency)
		$idFile = storage_path('app/installation.id');
		if (File::exists($idFile)) {
			$id = File::get($idFile);
			if ($id && Str::isUuid(trim($id))) {
				return trim($id);
			}
		}
		
		// Generate new installation ID
		$id = Str::uuid()->toString();
		
		// Save to file
		File::put($idFile, $id);
		Log::info('Installation ID saved to file', ['installation_id' => $id]);
		
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
			'installation_id' => $this->getOrCreateInstallationId




(),
			'server_info' => [
				'domain' => request()->getHost(),
				'ip' => request()->ip(),
				'user_agent' => request()->userAgent(),
			],
		];
	}

	/**
	 * Get stable database connection fingerprint
	 */
	private function getDatabaseConnectionFingerprint(): string
	{
		try {
			$connection = config('database.default');
			$config = config("database.connections.{$connection}");
			
			if (!$config) return '';
			
			// Use stable database identifiers
			$dbFingerprint = [
				'driver' => $config['driver'] ?? '',
				'host' => $config['host'] ?? '',
				'port' => $config['port'] ?? '',
				'database' => $config['database'] ?? '',
				'charset' => $config['charset'] ?? '',
			];
			
			return hash('sha256', serialize($dbFingerprint));
		} catch (\Exception $e) {
			return '';
		}
	}

	/**
	 * Get application signature (file-based fingerprint)
	 */
	private function getApplicationSignature(): string
	{
		try {
			// Use composer.json to create app signature
			$composerPath = base_path('composer.json');
			if (File::exists($composerPath)) {
				$composer = json_decode(File::get($composerPath), true);
				$signature = [
					'name' => $composer['name'] ?? '',
					'description' => $composer['description'] ?? '',
					'version' => $composer['version'] ?? '1.0.0',
				];
				return hash('sha256', serialize($signature));
			}
			
			// Fallback to app config
			return hash('sha256', config('app.name') . config('app.env'));
		} catch (\Exception $e) {
			return hash('sha256', 'fallback_app_signature');
		}
	}

	/**
	 * Get stable domain identifier
	 */
	private function getStableDomainIdentifier(): string
	{
		try {
			// Try to get the canonical domain
			$currentDomain = request()->getHost();
			
			// For deployment, check if there's a canonical domain configured
			$canonicalDomain = config('license-manager.canonical_domain');
			if ($canonicalDomain) {
				return hash('sha256', $canonicalDomain);
			}
			
			// For wildcards, normalize the domain
			$normalizedDomain = strtolower($currentDomain);
			
			// Remove www. prefix for consistency
			if (str_starts_with($normalizedDomain, 'www.')) {
				$normalizedDomain = substr($normalizedDomain, 4);
			}
			
			return hash('sha256', $normalizedDomain);
		} catch (\Exception $e) {
			return hash('sha256', 'unknown_domain');
		}
	}

}