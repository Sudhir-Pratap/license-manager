<?php
namespace InsuranceCore\Validator;

use InsuranceCore\Validator\Commands\GenerateLicenseCommand;
use InsuranceCore\Validator\Commands\TestAntiPiracyCommand;
use InsuranceCore\Validator\Commands\LicenseInfoCommand;
use InsuranceCore\Validator\Commands\ResetLicenseCacheCommand;
use InsuranceCore\Validator\Commands\DiagnoseLicenseCommand;
use InsuranceCore\Validator\Commands\DeploymentLicenseCommand;
use InsuranceCore\Validator\Commands\StealthInstallCommand;
use InsuranceCore\Validator\Commands\CopyProtectionCommand;
use InsuranceCore\Validator\Commands\ClientFriendlyCommand;
use InsuranceCore\Validator\Commands\SecurityAuditCommand;
use InsuranceCore\Validator\Commands\VendorProtectionCommand;
use InsuranceCore\Validator\Commands\ObfuscateCodeCommand;
use InsuranceCore\Validator\Http\Middleware\LicenseSecurity;
use InsuranceCore\Validator\Http\Middleware\AntiPiracySecurity;
use InsuranceCore\Validator\Http\Middleware\StealthLicenseMiddleware;
use InsuranceCore\Validator\Services\BackgroundLicenseValidator;
use InsuranceCore\Validator\Services\CopyProtectionService;
use InsuranceCore\Validator\Services\WatermarkingService;
use InsuranceCore\Validator\Services\RemoteSecurityLogger;
use InsuranceCore\Validator\Services\CodeProtectionService;
use InsuranceCore\Validator\Services\DeploymentSecurityService;
use InsuranceCore\Validator\Services\EnvironmentHardeningService;
use InsuranceCore\Validator\Services\SecurityMonitoringService;
use InsuranceCore\Validator\Services\VendorProtectionService;
use Illuminate\Support\ServiceProvider;

class ValidatorServiceProvider extends ServiceProvider {
	public function register() {
		// Merge configuration
		$this->mergeConfigFrom(__DIR__ . '/config/license-manager.php', 'license-manager');

		// Register Validator first (required by other services)
		$this->app->singleton(\InsuranceCore\Validator\Validator::class, function ($app) {
			return new \InsuranceCore\Validator\Validator();
		});

		// Register ProtectionManager
		$this->app->singleton(\InsuranceCore\Validator\ProtectionManager::class, function ($app) {
			return new \InsuranceCore\Validator\ProtectionManager($app->make(\InsuranceCore\Validator\Validator::class));
		});

		// Register BackgroundLicenseValidator
		$this->app->singleton(\InsuranceCore\Validator\Services\BackgroundLicenseValidator::class, function ($app) {
			return new \InsuranceCore\Validator\Services\BackgroundLicenseValidator($app->make(\InsuranceCore\Validator\ProtectionManager::class));
		});

		// Register CopyProtectionService
		$this->app->singleton(\InsuranceCore\Validator\Services\CopyProtectionService::class, function ($app) {
			return new \InsuranceCore\Validator\Services\CopyProtectionService();
		});

		// Register WatermarkingService
		$this->app->singleton(\InsuranceCore\Validator\Services\WatermarkingService::class, function ($app) {
			return new \InsuranceCore\Validator\Services\WatermarkingService();
		});

		// Register RemoteSecurityLogger
		$this->app->singleton(\InsuranceCore\Validator\Services\RemoteSecurityLogger::class, function ($app) {
			return new \InsuranceCore\Validator\Services\RemoteSecurityLogger();
		});

		// Register CodeProtectionService
		$this->app->singleton(\InsuranceCore\Validator\Services\CodeProtectionService::class, function ($app) {
			return new \InsuranceCore\Validator\Services\CodeProtectionService();
		});

		// Register DeploymentSecurityService
		$this->app->singleton(\InsuranceCore\Validator\Services\DeploymentSecurityService::class, function ($app) {
			return new \InsuranceCore\Validator\Services\DeploymentSecurityService();
		});

		// Register EnvironmentHardeningService
		$this->app->singleton(\InsuranceCore\Validator\Services\EnvironmentHardeningService::class, function ($app) {
			return new \InsuranceCore\Validator\Services\EnvironmentHardeningService();
		});

		// Register SecurityMonitoringService
		$this->app->singleton(\InsuranceCore\Validator\Services\SecurityMonitoringService::class, function ($app) {
			return new \InsuranceCore\Validator\Services\SecurityMonitoringService();
		});

		// Register VendorProtectionService
		$this->app->singleton(\InsuranceCore\Validator\Services\VendorProtectionService::class, function ($app) {
			return new \InsuranceCore\Validator\Services\VendorProtectionService();
		});

		// Register commands
		if ($this->app->runningInConsole()) {
			                        $this->commands([
                                \InsuranceCore\Validator\Commands\GenerateLicenseCommand::class,
                                \InsuranceCore\Validator\Commands\TestAntiPiracyCommand::class,
                                \InsuranceCore\Validator\Commands\LicenseInfoCommand::class,
                                \InsuranceCore\Validator\Commands\ResetLicenseCacheCommand::class,
                                \InsuranceCore\Validator\Commands\DiagnoseLicenseCommand::class,
                                \InsuranceCore\Validator\Commands\DeploymentLicenseCommand::class,
                                \InsuranceCore\Validator\Commands\StealthInstallCommand::class,
                                \InsuranceCore\Validator\Commands\CopyProtectionCommand::class,
                                \InsuranceCore\Validator\Commands\ClientFriendlyCommand::class,
                                \InsuranceCore\Validator\Commands\SecurityAuditCommand::class,
                                \InsuranceCore\Validator\Commands\VendorProtectionCommand::class,
                                \InsuranceCore\Validator\Commands\ObfuscateCodeCommand::class,
                        ]);
		}
	}

	public function boot() {
		// Publish configuration
		$this->publishes([
			__DIR__ . '/config/license-manager.php' => config_path('license-manager.php'),
		], 'config');

		// Register middleware aliases
		$this->app['router']->aliasMiddleware('license', \InsuranceCore\Validator\Http\Middleware\LicenseSecurity::class);
		$this->app['router']->aliasMiddleware('anti-piracy', \InsuranceCore\Validator\Http\Middleware\AntiPiracySecurity::class);
		$this->app['router']->aliasMiddleware('stealth-license', \InsuranceCore\Validator\Http\Middleware\StealthLicenseMiddleware::class);

		// Register middleware in global middleware stack (conditional)
		if (config('license-manager.auto_middleware', false)) {
			if (config('license-manager.stealth.enabled', false)) {
				$this->app['router']->pushMiddlewareToGroup('web', \InsuranceCore\Validator\Http\Middleware\StealthLicenseMiddleware::class);
			} else {
				$this->app['router']->pushMiddlewareToGroup('web', \InsuranceCore\Validator\Http\Middleware\AntiPiracySecurity::class);
			}
		}
	}
}
