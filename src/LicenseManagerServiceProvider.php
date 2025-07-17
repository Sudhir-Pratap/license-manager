<?php
namespace Acecoderz\LicenseManager;

use Acecoderz\LicenseManager\Commands\GenerateLicenseCommand;
use Acecoderz\LicenseManager\Commands\TestAntiPiracyCommand;
use Acecoderz\LicenseManager\Commands\LicenseInfoCommand;
use Acecoderz\LicenseManager\Commands\ResetLicenseCacheCommand;
use Acecoderz\LicenseManager\Commands\DiagnoseLicenseCommand;
use Acecoderz\LicenseManager\Http\Middleware\LicenseSecurity;
use Acecoderz\LicenseManager\Http\Middleware\AntiPiracySecurity;
use Illuminate\Support\ServiceProvider;

class LicenseManagerServiceProvider extends ServiceProvider {
	public function register() {
		// Merge configuration
		$this->mergeConfigFrom(__DIR__ . '/config/license-manager.php', 'license-manager');

		// Register AntiPiracyManager
		$this->app->singleton(AntiPiracyManager::class, function ($app) {
			return new AntiPiracyManager($app->make(LicenseManager::class));
		});

		// Register commands
		if ($this->app->runningInConsole()) {
			$this->commands([
				GenerateLicenseCommand::class,
				TestAntiPiracyCommand::class,
				LicenseInfoCommand::class,
				ResetLicenseCacheCommand::class,
				DiagnoseLicenseCommand::class,
			]);
		}
	}

	public function boot() {
		// Publish configuration
		$this->publishes([
			__DIR__ . '/config/license-manager.php' => config_path('license-manager.php'),
		], 'config');

		// Register middleware
		$this->app['router']->aliasMiddleware('license', LicenseSecurity::class);
		$this->app['router']->aliasMiddleware('anti-piracy', AntiPiracySecurity::class);
	}
}