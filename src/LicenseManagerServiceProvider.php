<?php
namespace Acecoderz\LicenseManager;

use Acecoderz\LicenseManager\Commands\GenerateLicenseCommand;
use Acecoderz\LicenseManager\Commands\TestAntiPiracyCommand;
use Acecoderz\LicenseManager\Commands\LicenseInfoCommand;
use Acecoderz\LicenseManager\Commands\ResetLicenseCacheCommand;
use Acecoderz\LicenseManager\Commands\DiagnoseLicenseCommand;
use Acecoderz\LicenseManager\Commands\DeploymentLicenseCommand;
use Acecoderz\LicenseManager\Commands\StealthInstallCommand;
use Acecoderz\LicenseManager\Commands\CopyProtectionCommand;
use Acecoderz\LicenseManager\Commands\ClientFriendlyCommand;
use Acecoderz\LicenseManager\Commands\SecurityAuditCommand;
use Acecoderz\LicenseManager\Commands\VendorProtectionCommand;
use Acecoderz\LicenseManager\Commands\ObfuscateCodeCommand;
use Acecoderz\LicenseManager\Http\Middleware\LicenseSecurity;
use Acecoderz\LicenseManager\Http\Middleware\AntiPiracySecurity;
use Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware;
use Acecoderz\LicenseManager\Services\BackgroundLicenseValidator;
use Acecoderz\LicenseManager\Services\CopyProtectionService;
use Acecoderz\LicenseManager\Services\WatermarkingService;
use Acecoderz\LicenseManager\Services\RemoteSecurityLogger;
use Acecoderz\LicenseManager\Services\CodeProtectionService;
use Acecoderz\LicenseManager\Services\DeploymentSecurityService;
use Acecoderz\LicenseManager\Services\EnvironmentHardeningService;
use Acecoderz\LicenseManager\Services\SecurityMonitoringService;
use Acecoderz\LicenseManager\Services\VendorProtectionService;
use Illuminate\Support\ServiceProvider;

class LicenseManagerServiceProvider extends ServiceProvider {
	public function register() {
		// Merge configuration
		$this->mergeConfigFrom(__DIR__ . '/config/license-manager.php', 'license-manager');

		// Register LicenseManager first (required by other services)
		$this->app->singleton(LicenseManager::class, function ($app) {
			return new LicenseManager();
		});

		// Register AntiPiracyManager
		$this->app->singleton(AntiPiracyManager::class, function ($app) {
			return new AntiPiracyManager($app->make(LicenseManager::class));
		});

		// Register BackgroundLicenseValidator
		$this->app->singleton(BackgroundLicenseValidator::class, function ($app) {
			return new BackgroundLicenseValidator($app->make(AntiPiracyManager::class));
		});

		// Register CopyProtectionService
		$this->app->singleton(CopyProtectionService::class, function ($app) {
			return new CopyProtectionService();
		});

		// Register WatermarkingService
		$this->app->singleton(WatermarkingService::class, function ($app) {
			return new WatermarkingService();
		});

		// Register RemoteSecurityLogger
		$this->app->singleton(RemoteSecurityLogger::class, function ($app) {
			return new RemoteSecurityLogger();
		});

		// Register CodeProtectionService
		$this->app->singleton(CodeProtectionService::class, function ($app) {
			return new CodeProtectionService();
		});

		// Register DeploymentSecurityService
		$this->app->singleton(DeploymentSecurityService::class, function ($app) {
			return new DeploymentSecurityService();
		});

		// Register EnvironmentHardeningService
		$this->app->singleton(EnvironmentHardeningService::class, function ($app) {
			return new EnvironmentHardeningService();
		});

		// Register SecurityMonitoringService
		$this->app->singleton(SecurityMonitoringService::class, function ($app) {
			return new SecurityMonitoringService();
		});

		// Register VendorProtectionService
		$this->app->singleton(VendorProtectionService::class, function ($app) {
			return new VendorProtectionService();
		});

		// Register commands
		if ($this->app->runningInConsole()) {
			                        $this->commands([
                                GenerateLicenseCommand::class,
                                TestAntiPiracyCommand::class,
                                LicenseInfoCommand::class,
                                ResetLicenseCacheCommand::class,
                                DiagnoseLicenseCommand::class,
                                DeploymentLicenseCommand::class,
                                StealthInstallCommand::class,
                                CopyProtectionCommand::class,
                                ClientFriendlyCommand::class,
                                SecurityAuditCommand::class,
                                VendorProtectionCommand::class,
                                ObfuscateCodeCommand::class,
                        ]);
		}
	}

	public function boot() {
		// Publish configuration
		$this->publishes([
			__DIR__ . '/config/license-manager.php' => config_path('license-manager.php'),
		], 'config');

		// Register middleware aliases
		$this->app['router']->aliasMiddleware('license', LicenseSecurity::class);
		$this->app['router']->aliasMiddleware('anti-piracy', AntiPiracySecurity::class);
		$this->app['router']->aliasMiddleware('stealth-license', StealthLicenseMiddleware::class);
		
		// Register middleware in global middleware stack (conditional)
		if (config('license-manager.auto_middleware', false)) {
			if (config('license-manager.stealth.enabled', false)) {
				$this->app['router']->pushMiddlewareToGroup('web', StealthLicenseMiddleware::class);
			} else {
				$this->app['router']->pushMiddlewareToGroup('web', AntiPiracySecurity::class);
			}
		}
	}
}