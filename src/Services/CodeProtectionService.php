<?php

namespace Acecoderz\LicenseManager\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CodeProtectionService
{
    /**
     * Apply code obfuscation and protection measures
     */
    public function applyProtection(): void
    {
        if (!config('license-manager.code_protection.obfuscation_enabled', true)) {
            return;
        }

        $this->obfuscateCriticalFunctions();
        $this->addRuntimeIntegrityChecks();
        $this->implementAntiDebugMeasures();
        $this->addWatermarking();
    }

    /**
     * Obfuscate critical license functions
     */
    public function obfuscateCriticalFunctions(): void
    {
        // This would integrate with tools like PHP-Parser to modify AST
        // For now, we'll implement string-based obfuscation

        $criticalFiles = [
            'src/LicenseManager.php',
            'src/AntiPiracyManager.php',
        ];

        foreach ($criticalFiles as $file) {
            $filePath = __DIR__ . '/../' . $file;
            if (File::exists($filePath)) {
                $this->obfuscateFile($filePath);
            }
        }
    }

    /**
     * Add runtime integrity checks
     */
    public function addRuntimeIntegrityChecks(): void
    {
        // Add integrity verification at runtime
        $integrityHash = $this->generateIntegrityHash();
        Cache::put('code_integrity_hash', $integrityHash, now()->addDays(30));

        // This would be called during license validation
        $this->verifyIntegrity($integrityHash);
    }

    /**
     * Implement anti-debugging measures
     */
    public function implementAntiDebugMeasures(): void
    {
        // Detect debugging tools and development environments
        $debugIndicators = [
            'xdebug' => extension_loaded('xdebug'),
            'debug_backtrace' => count(debug_backtrace()) > 10,
            'eval_usage' => $this->detectEvalUsage(),
            'development_headers' => $this->detectDevelopmentHeaders(),
        ];

        if (array_filter($debugIndicators)) {
            Log::warning('Debugging environment detected', array_keys(array_filter($debugIndicators)));
        }
    }

    /**
     * Add invisible watermarking to HTML output
     */
    public function addWatermarking(): void
    {
        if (!config('license-manager.code_protection.watermarking', true)) {
            return;
        }

        // This would hook into Laravel's response rendering
        // For now, we'll document the implementation approach
    }

    /**
     * Generate integrity hash for critical files
     */
    public function generateIntegrityHash(): string
    {
        $criticalFiles = [
            'src/LicenseManager.php',
            'src/AntiPiracyManager.php',
            'src/config/license-manager.php',
        ];

        $hashes = [];
        foreach ($criticalFiles as $file) {
            $filePath = __DIR__ . '/../' . $file;
            if (File::exists($filePath)) {
                $hashes[] = hash_file('sha256', $filePath);
            }
        }

        return hash('sha256', implode('', $hashes));
    }

    /**
     * Verify code integrity
     */
    public function verifyIntegrity(string $expectedHash): bool
    {
        $currentHash = $this->generateIntegrityHash();

        if ($currentHash !== $expectedHash) {
            app(RemoteSecurityLogger::class)->critical('Code integrity violation detected', [
                'expected_hash' => $expectedHash,
                'current_hash' => $currentHash,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Detect eval() usage (security risk)
     */
    public function detectEvalUsage(): bool
    {
        // Check if eval function is disabled
        $disabled = explode(',', ini_get('disable_functions'));
        return !in_array('eval', $disabled);
    }

    /**
     * Detect development headers that might indicate debugging
     */
    public function detectDevelopmentHeaders(): bool
    {
        $headers = [
            'X-Debug-Token',
            'X-Debug-Token-Link',
            'X-Php-Debugbar',
        ];

        foreach ($headers as $header) {
            if (request()->hasHeader($header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Basic file obfuscation (string replacement)
     */
    public function obfuscateFile(string $filePath): void
    {
        $content = File::get($filePath);

        // Simple variable name obfuscation for sensitive functions
        $replacements = [
            'validateLicense' => 'vl' . Str::random(8),
            'generateHardwareFingerprint' => 'ghf' . Str::random(8),
            'validateAntiPiracy' => 'vap' . Str::random(8),
        ];

        foreach ($replacements as $original => $obfuscated) {
            $content = str_replace($original, $obfuscated, $content);
        }

        // Store mapping for deobfuscation if needed
        Cache::put('obfuscation_map_' . basename($filePath), $replacements, now()->addYears(1));

        File::put($filePath, $content);
    }

    /**
     * Dynamic validation key generation
     */
    public function generateDynamicKey(): string
    {
        $components = [
            config('app.key'),
            config('license-manager.license_key'),
            now()->format('Y-m-d-H'),
            request()->ip(),
        ];

        return hash('sha256', implode('|', $components));
    }
}
