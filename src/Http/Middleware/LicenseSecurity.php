<?php
namespace Acecoderz\LicenseManager\Http\Middleware;

use Acecoderz\LicenseManager\LicenseManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LicenseSecurity {
	protected $licenseManager;

	public function __construct() {
		// Don't inject dependencies in constructor to avoid circular dependencies
	}

	protected function getLicenseManager() {
		if (!$this->licenseManager) {
			$this->licenseManager = app(LicenseManager::class);
		}
		return $this->licenseManager;
	}

	public function handle(Request $request, Closure $next) {
		// Skip validation for certain routes
		if ($this->shouldSkipValidation($request)) {
			return $next($request);
		}
		$licenseKey    = config('license-manager.license_key');
		$productId     = config('license-manager.product_id');
		$clientId      = config('license-manager.client_id');
		$currentDomain = $request->getHost();
		$currentIp     = $request->ip();

		if (! $this->getLicenseManager()->validateLicense($licenseKey, $productId, $currentDomain, $currentIp, $clientId)) {
			Log::error('License check failed, aborting request', [
				'license_key' => $licenseKey,
				'product_id'  => $productId,
				'domain'      => $currentDomain,
				'ip'          => $currentIp,
				'client_id'   => $clientId,
			]);
			abort(403, 'Invalid or unauthorized license.');
		}

		return $next($request);
	}

	protected function shouldSkipValidation(Request $request): bool {
		$skipRoutes = config('license-manager.skip_routes', []);
		$path = $request->path();

		// Skip specific routes
		foreach ($skipRoutes as $route) {
			$cleanRoute = trim($route, '/');
			if (str_starts_with($path, $cleanRoute)) {
				return true;
			}
		}

		// Skip file extensions (assets, images, etc.)
		$skipExtensions = ['.css', '.js', '.png', '.jpg', '.gif', '.ico', '.svg', '.woff', '.woff2'];
		foreach ($skipExtensions as $ext) {
			if (str_ends_with($path, $ext)) {
				return true;
			}
		}

		// Allow bypass in local environment (unless explicitly disabled for testing)
		if (app()->environment('local') && !config('license-manager.disable_local_bypass', false)) {
			return true;
		}

		return false;
	}
}