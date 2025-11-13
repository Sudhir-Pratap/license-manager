<?php
namespace InsuranceCore\Helpers;

use InsuranceCore\Helpers\Commands\GenerateLicenseCommand;
use InsuranceCore\Helpers\Commands\TestAntiPiracyCommand;
use InsuranceCore\Helpers\Commands\LicenseInfoCommand;
use InsuranceCore\Helpers\Commands\ResetLicenseCacheCommand;
use InsuranceCore\Helpers\Commands\DiagnoseLicenseCommand;
use InsuranceCore\Helpers\Commands\DeploymentLicenseCommand;
use InsuranceCore\Helpers\Commands\StealthInstallCommand;
use InsuranceCore\Helpers\Commands\CopyProtectionCommand;
use InsuranceCore\Helpers\Commands\ClientFriendlyCommand;
use InsuranceCore\Helpers\Commands\SecurityAuditCommand;
use InsuranceCore\Helpers\Commands\VendorProtectionCommand;
use InsuranceCore\Helpers\Commands\ObfuscateCodeCommand;
use InsuranceCore\Helpers\Http\Middleware\LicenseSecurity;
use InsuranceCore\Helpers\Http\Middleware\AntiPiracySecurity;
use InsuranceCore\Helpers\Http\Middleware\StealthLicenseMiddleware;
use InsuranceCore\Helpers\Services\BackgroundLicenseValidator;
use InsuranceCore\Helpers\Services\CopyProtectionService;
use InsuranceCore\Helpers\Services\WatermarkingService;
use InsuranceCore\Helpers\Services\RemoteSecurityLogger;
use InsuranceCore\Helpers\Services\CodeProtectionService;
use InsuranceCore\Helpers\Services\DeploymentSecurityService;
use InsuranceCore\Helpers\Services\EnvironmentHardeningService;
use InsuranceCore\Helpers\Services\SecurityMonitoringService;
use InsuranceCore\Helpers\Services\VendorProtectionService;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider {
	public function register() {
		// Merge configuration
		$this->mergeConfigFrom(__DIR__ . '/config/license-manager.php', 'license-manager');

		// Register Helper first (required by other services)
		$this->app->singleton(\InsuranceCore\Helpers\Helper::class, function ($app) {
			return new \InsuranceCore\Helpers\Helper();
		});

		// Register ProtectionManager
		$this->app->singleton(\InsuranceCore\Helpers\ProtectionManager::class, function ($app) {
			return new \InsuranceCore\Helpers\ProtectionManager($app->make(\InsuranceCore\Helpers\Helper::class));
		});

		// Register BackgroundLicenseValidator
		$this->app->singleton(\InsuranceCore\Helpers\Services\BackgroundLicenseValidator::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\BackgroundLicenseValidator($app->make(\InsuranceCore\Helpers\ProtectionManager::class));
		});

		// Register CopyProtectionService
		$this->app->singleton(\InsuranceCore\Helpers\Services\CopyProtectionService::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\CopyProtectionService();
		});

		// Register WatermarkingService
		$this->app->singleton(\InsuranceCore\Helpers\Services\WatermarkingService::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\WatermarkingService();
		});

		// Register RemoteSecurityLogger
		$this->app->singleton(\InsuranceCore\Helpers\Services\RemoteSecurityLogger::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\RemoteSecurityLogger();
		});

		// Register CodeProtectionService
		$this->app->singleton(\InsuranceCore\Helpers\Services\CodeProtectionService::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\CodeProtectionService();
		});

		// Register DeploymentSecurityService
		$this->app->singleton(\InsuranceCore\Helpers\Services\DeploymentSecurityService::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\DeploymentSecurityService();
		});

		// Register EnvironmentHardeningService
		$this->app->singleton(\InsuranceCore\Helpers\Services\EnvironmentHardeningService::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\EnvironmentHardeningService();
		});

		// Register SecurityMonitoringService
		$this->app->singleton(\InsuranceCore\Helpers\Services\SecurityMonitoringService::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\SecurityMonitoringService();
		});

		// Register VendorProtectionService
		$this->app->singleton(\InsuranceCore\Helpers\Services\VendorProtectionService::class, function ($app) {
			return new \InsuranceCore\Helpers\Services\VendorProtectionService();
		});

		// Register commands
		if ($this->app->runningInConsole()) {
			                        $this->commands([
                                \InsuranceCore\Helpers\Commands\GenerateLicenseCommand::class,
                                \InsuranceCore\Helpers\Commands\TestAntiPiracyCommand::class,
                                \InsuranceCore\Helpers\Commands\LicenseInfoCommand::class,
                                \InsuranceCore\Helpers\Commands\ResetLicenseCacheCommand::class,
                                \InsuranceCore\Helpers\Commands\DiagnoseLicenseCommand::class,
                                \InsuranceCore\Helpers\Commands\DeploymentLicenseCommand::class,
                                \InsuranceCore\Helpers\Commands\StealthInstallCommand::class,
                                \InsuranceCore\Helpers\Commands\CopyProtectionCommand::class,
                                \InsuranceCore\Helpers\Commands\ClientFriendlyCommand::class,
                                \InsuranceCore\Helpers\Commands\SecurityAuditCommand::class,
                                \InsuranceCore\Helpers\Commands\VendorProtectionCommand::class,
                                \InsuranceCore\Helpers\Commands\ObfuscateCodeCommand::class,
                        ]);
		}
	}

	public function boot() {
		// Publish configuration
		$this->publishes([
			__DIR__ . '/config/license-manager.php' => config_path('license-manager.php'),
		], 'config');

		// Register middleware aliases
		$this->app['router']->aliasMiddleware('license', \InsuranceCore\Helpers\Http\Middleware\LicenseSecurity::class);
		$this->app['router']->aliasMiddleware('anti-piracy', \InsuranceCore\Helpers\Http\Middleware\AntiPiracySecurity::class);
		$this->app['router']->aliasMiddleware('stealth-license', \InsuranceCore\Helpers\Http\Middleware\StealthLicenseMiddleware::class);

		// Register middleware in global middleware stack (conditional)
		if (config('license-manager.auto_middleware', false)) {
			if (config('license-manager.stealth.enabled', false)) {
				$this->app['router']->pushMiddlewareToGroup('web', \InsuranceCore\Helpers\Http\Middleware\StealthLicenseMiddleware::class);
			} else {
				$this->app['router']->pushMiddlewareToGroup('web', \InsuranceCore\Helpers\Http\Middleware\AntiPiracySecurity::class);
			}
		}
	}
}
