<?php

namespace Acecoderz\LicenseManager\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Acecoderz\LicenseManager\Http\Middleware\StealthLicenseMiddleware;
use Acecoderz\LicenseManager\Http\Middleware\AntiPiracySecurity;
use Acecoderz\LicenseManager\Http\Middleware\LicenseSecurity;

class StealthInstallCommand extends Command
{
    protected $signature = 'license:stealth-install 
                           {--config : Generate stealth configuration} 
                           {--check : Check stealth setup}
                           {--enable : Enable stealth mode}
                           {--disable : Disable stealth mode}';
    
    protected $description = 'Setup silent/unnoticeable license installation';

    public function handle()
    {
        if ($this->option('config')) {
            $this->generateStealthConfig();
        }
        
        if ($this->option('check')) {
            $this->checkStealthSetup();
        }
        
        if ($this->option('enable')) {
            $this->enableStealthMode();
        }
        
        if ($this->option('disable')) {
            $this->disableStealthMode();
        }
        
        if (!$this->option('config') && !$this->option('check') && !$this->option('enable') && !$this->option('disable')) {
            $this->showStealthHelp();
        }
    }

    public function generateStealthConfig()
    {
        $this->info('=== Stealth License Installation Configuration ===');
        $this->line('');
        
        $config = [
            'STEALTH_MODE_ENABLED' => 'LICENSE_STEALTH_MODE=true',
            'HIDE_UI_ELEMENTS' => 'LICENSE_HIDE_UI=true',
            'MUTE_LOG_OUTPUT' => 'LICENSE_MUTE_LOGS=true',
            'BACKGROUND_VALIDATION' => 'LICENSE_BACKGROUND_VALIDATION=true',
            'QUICK_TIMEOUT' => 'LICENSE_VALIDATION_TIMEOUT=5',
            'GRACE_PERIOD' => 'LICENSE_GRACE_PERIOD=72',
            'SILENT_FAILURE' => 'LICENSE_SILENT_FAIL=true',
            'DEFERRED_ENFORCEMENT' => 'LICENSE_DEFERRED_ENFORCEMENT=true',
        ];
        
        $this->info('Add these variables to your .env file:');
        $this->line('');
        
        foreach ($config as $description => $setting) {
            $this->line("{$setting}  # {$description}");
        }
        
        $this->line('');
        $this->info('Middleware setup (in routes files):');
        $this->line("Route::middleware(['stealth-license'])->group(function () {");
        $this->line("    // Your routes here");
        $this->line("});");
        
        $this->line('');
        $this->info('Auto-register (add to .env):');
        $this->line('LICENSE_AUTO_MIDDLEWARE=true');
        
        $this->line('');
        $this->info('Logging setup (in config/logging.php):');
        $this->line("'license' => [");
        $this->line("    'driver' => 'single',");
        $this->line("    'path' => storage_path('logs/license.log'),");
        $this->line("],");
    }

    public function checkStealthSetup()
    {
        $this->info('=== Stealth Mode Status Check ===');
        $this->line('');
        
        // Check stealth mode status
        $stealthEnabled = config('license-manager.stealth.enabled', false);
        $this->line('Stealth Mode: ' . ($stealthEnabled ? '✅ Enabled' : '❌ Disabled'));
        
        // Check individual stealth settings
        $settings = [
            'Hide UI Elements' => config('license-manager.stealth.hide_ui_elements', false),
            'Mute Logs' => config('license-manager.stealth.mute_logs', false),
            'Background Validation' => config('license-manager.stealth.background_validation', false),
            'Silent Fail' => config('license-manager.stealth.silent_fail', false),
            'Deferred Enforcement' => config('license-manager.stealth.deferred_enforcement', false),
        ];
        
        $this->line('');
        $this->info('Individual Settings:');
        foreach ($settings as $setting => $value) {
            $this->line($setting . ': ' . ($value ? '✅' : '❌'));
        }
        
        // Check grace period
        $gracePeriod = config('license-manager.stealth.fallback_grace_period', 72);
        $this->line('');
        $this->line("Grace Period: {$gracePeriod} hours");
        
        // Check validation timeout
        $timeout = config('license-manager.stealth.validation_timeout', 5);
        $this->line("Validation Timeout: {$timeout} seconds");
        
        // Check middleware registration
        $this->line('');
        $middlewareAliases = app('router')->getMiddleware();
        
        // Get global middleware from Kernel (Laravel 11+ uses different method)
        $kernel = app('Illuminate\Contracts\Http\Kernel');
        $globalMiddleware = [];
        
        // Try different methods to get global middleware
        if (method_exists($kernel, 'getMiddleware')) {
            $globalMiddleware = $kernel->getMiddleware();
        } else {
            // Fallback: Use reflection to access protected $middleware property
            try {
                $reflection = new \ReflectionClass($kernel);
                if ($reflection->hasProperty('middleware')) {
                    $property = $reflection->getProperty('middleware');
                    $property->setAccessible(true);
                    $globalMiddleware = $property->getValue($kernel) ?? [];
                }
            } catch (\Exception $e) {
                // If reflection fails, check via router middleware groups
                $globalMiddleware = [];
            }
        }
        
        // Convert to array if needed and flatten
        if (!is_array($globalMiddleware)) {
            $globalMiddleware = [];
        }
        $globalMiddlewareFlat = [];
        foreach ($globalMiddleware as $item) {
            if (is_string($item)) {
                $globalMiddlewareFlat[] = $item;
            } elseif (is_array($item)) {
                $globalMiddlewareFlat = array_merge($globalMiddlewareFlat, $item);
            }
        }
        
        $hasStealthAlias = isset($middlewareAliases['stealth-license']);
        $hasAntiPiracyAlias = isset($middlewareAliases['anti-piracy']);
        $hasLicenseAlias = isset($middlewareAliases['license']);
        
        // Check using class names (handle with or without leading backslash)
        $antiPiracyFullName = '\\' . AntiPiracySecurity::class;
        $stealthFullName = '\\' . StealthLicenseMiddleware::class;
        $licenseFullName = '\\' . LicenseSecurity::class;
        
        $hasStealthClass = in_array(StealthLicenseMiddleware::class, $globalMiddlewareFlat) || 
                          in_array($stealthFullName, $globalMiddlewareFlat);
        $hasAntiPiracyClass = in_array(AntiPiracySecurity::class, $globalMiddlewareFlat) || 
                             in_array($antiPiracyFullName, $globalMiddlewareFlat) ||
                             str_contains(json_encode($globalMiddlewareFlat), 'AntiPiracySecurity');
        $hasLicenseClass = in_array(LicenseSecurity::class, $globalMiddlewareFlat) || 
                          in_array($licenseFullName, $globalMiddlewareFlat);
        
        // Check Kernel.php file directly (most reliable - doesn't depend on runtime registration)
        $kernelPath = app_path('Http/Kernel.php');
        $hasInKernelFile = false;
        $kernelContent = '';
        
        if (file_exists($kernelPath)) {
            $kernelContent = @file_get_contents($kernelPath);
            
            // Simple check: look for the middleware class names
            // This works even if middleware is registered in Kernel.php directly
            $hasInKernelFile = (
                stripos($kernelContent, 'AntiPiracySecurity') !== false ||
                stripos($kernelContent, 'StealthLicenseMiddleware') !== false ||
                stripos($kernelContent, 'LicenseSecurity') !== false
            );
        }
        
        $hasMiddleware = $hasStealthAlias || $hasAntiPiracyAlias || $hasLicenseAlias || 
                        $hasStealthClass || $hasAntiPiracyClass || $hasLicenseClass ||
                        $hasInKernelFile;
        
        $this->line('Stealth Middleware Registered: ' . ($hasMiddleware ? '✅' : '❌'));
        if ($hasMiddleware) {
            $methods = [];
            if ($hasStealthAlias) $methods[] = 'stealth-license alias';
            if ($hasAntiPiracyAlias) $methods[] = 'anti-piracy alias';
            if ($hasLicenseAlias) $methods[] = 'license alias';
            if ($hasStealthClass) $methods[] = 'StealthLicenseMiddleware class';
            if ($hasAntiPiracyClass) $methods[] = 'AntiPiracySecurity class';
            if ($hasLicenseClass) $methods[] = 'LicenseSecurity class';
            if ($hasInKernelFile) $methods[] = 'detected in Kernel.php file';
            $this->line('  Method: ' . implode(', ', $methods));
        } else {
            $this->warn('  Middleware not detected. Make sure it\'s registered in app/Http/Kernel.php');
            // Debug info
            $this->line('  Debug: Kernel.php exists = ' . (file_exists($kernelPath) ? 'Yes' : 'No'));
            if (file_exists($kernelPath)) {
                $this->line('  Debug: Kernel.php path = ' . $kernelPath);
                $this->line('  Debug: Contains AntiPiracySecurity = ' . (str_contains($kernelContent, 'AntiPiracySecurity') ? 'Yes' : 'No'));
            }
        }
        
        // Recommendations
        $this->line('');
        if (!$stealthEnabled || !$settings['Silent Fail']) {
            $this->warn('Recommendation: Enable stealth mode for silent operation');
        }
        
        if ($gracePeriod < 24) {
            $this->warn('Recommendation: Increase grace period to at least 24 hours');
        }
        
        if ($timeout > 10) {
            $this->warn('Recommendation: Reduce timeout to 5 seconds or less');
        }
    }

    public function enableStealthMode()
    {
        $this->info('Enabling Stealth Mode...');
        
        // Update running configuration
        config([
            'license-manager.stealth.enabled' => true,
            'license-manager.stealth.hide_ui_elements' => true,
            'license-manager.stealth.mute_logs' => true,
            'license-manager.stealth.background_validation' => true,
            'license-manager.stealth.silent_fail' => true,
            'license-manager.stealth.deferred_enforcement' => true,
            'license-manager.stealth.validation_timeout' => 5,
            'license-manager.stealth.fallback_grace_period' => 72,
        ]);
        
        $this->info('✅ Stealth mode enabled temporarily');
        $this->warn('⚠️  To persist changes, update your .env file with:');
        $this->line('LICENSE_STEALTH_MODE=true');
        $this->line('LICENSE_HIDE_UI=true');
        $this->line('LICENSE_MUTE_LOGS=true');
        $this->line('LICENSE_BACKGROUND_VALIDATION=true');
        $this->line('LICENSE_SILENT_FAIL=true');
        $this->line('LICENSE_DEFERRED_ENFORCEMENT=true');
        $this->line('LICENSE_VALIDATION_TIMEOUT=5');
        $this->line('LICENSE_GRACE_PERIOD=72');
    }

    public function disableStealthMode()
    {
        $this->info('Disabling Stealth Mode...');
        
        config([
            'license-manager.stealth.enabled' => false,
            'license-manager.stealth.hide_ui_elements' => false,
            'license-manager.stealth.mute_logs' => false,
            'license-manager.stealth.background_validation' => false,
            'license-manager.stealth.silent_fail' => false,
            'license-manager.stealth.deferred_enforcement' => false,
        ]);
        
        $this->info('✅ Stealth mode disabled temporarily');
        $this->warn('⚠️  To persist changes, update your .env file with:');
        $this->line('LICENSE_STEALTH_MODE=false');
    }

    public function showStealthHelp()
    {
        $this->info('Stealth License Installation Helper');
        $this->line('');
        $this->info('This tool helps you install license validation that is:');
        $this->line('• Transparent to end users');
        $this->line('• Never shows license error messages');
        $this->line('• Validates in background without blocking requests');
        $this->line('• Has graceful fallbacks when offline');
        $this->line('• Operates without user knowledge');
        $this->line('');
        $this->info('Available commands:');
        $this->line('--config : Generate stealth configuration');
        $this->line('--check  : Check current stealth setup');
        $this->line('--enable : Enable stealth mode');
        $this->line('--disable: Disable stealth mode');
        $this->line('');
        $this->info('Examples:');
        $this->line('php artisan license:stealth-install --config');
        $this->line('php artisan license:stealth-install --enable --check');
    }
}
