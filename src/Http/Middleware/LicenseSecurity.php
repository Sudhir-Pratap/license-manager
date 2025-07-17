<?php
namespace Acecoderz\LicenseManager\Http\Middleware;

use Acecoderz\LicenseManager\LicenseManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LicenseSecurity {
	protected $licenseManager;

	public function __construct(LicenseManager $licenseManager) {
		$this->licenseManager = $licenseManager;
	}

	public function handle(Request $request, Closure $next) {
		$licenseKey    = config('license-manager.license_key');
		$productId     = config('license-manager.product_id');
		$clientId      = config('license-manager.client_id');
		$currentDomain = $request->getHost();
		$currentIp     = $request->ip();

		if (! $this->licenseManager->validateLicense($licenseKey, $productId, $currentDomain, $currentIp, $clientId)) {
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
}