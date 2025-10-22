<?php

namespace Acecoderz\LicenseManager\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CopyProtectionService
{
    /**
     * Detect potential reselling activity
     */
    public function detectResellingBehavior(array $context = []): bool
    {
        $suspiciousIndicators = [
            'multiple_domains' => $this->checkMultipleDomainUsage(),
            'usage_patterns' => $this->analyzeUsagePatterns(),
            'deployment_patterns' => $this->analyzeDeploymentPatterns(),
            'code_modifications' => $this->detectCodeModifications(),
            'network_behavior' => $this->analyzeNetworkBehavior(),
            'installation_clustering' => $this->checkInstallationClustering(),
        ];

        $score = $this->calculateSuspiciousScore($suspiciousIndicators);
        $threshold = config('license-manager.anti_reselling.threshold_score', 75);

        if ($score >= $threshold) {
            $this->handlePotentiallySuspiciousActivity($suspiciousIndicators, $score);
            return true;
        }

        return false;
    }

    /**
     * Check for multiple domains using same license
     */
    private function checkMultipleDomainUsage(): int
    {
        $domainKey = 'license_domains_' . md5(config('license-manager.license_key'));
        $domains = Cache::get($domainKey, []);
        
        $currentDomain = request()->getHost();
        if (!in_array($currentDomain, $domains)) {
            $domains[] = $currentDomain;
            Cache::put($domainKey, $domains, now()->addDays(30));
        }

        // Allow max 2 domains per license
        $maxAllowed = config('license-manager.anti_reselling.max_domains', 2);
        if (count($domains) > $maxAllowed) {
            Log::channel('security')->critical('Multiple domains detected', [
                'domains' => $domains,
                'license_key' => config('license-manager.license_key'),
                'excess_count' => count($domains) - $maxAllowed,
            ]);
            return 50; // High suspicion score
        }

        return count($domains) > 1 ? 20 : 0;
    }

    /**
     * Analyze usage patterns for suspicious behavior
     */
    private function analyzeUsagePatterns(): int
    {
        $usageKey = 'usage_pattern_' . md5(config('license-manager.license_key'));
        $patterns = Cache::get($usageKey, []);

        $currentPattern = [
            'time' => now()->hour,
            'user_agent' => substr(request()->userAgent(), 0, 50),
            'referer' => request()->header('referer'),
            'ip_range' => $this->getIPRange(request()->ip()),
            'session_fingerprint' => $this->generateSessionFingerprint(),
        ];

        $patterns[] = $currentPattern;
        
        // Keep only last 48 hours of data
        $cutoff = now()->subHours(48);
        $patterns = array_filter($patterns, function($pattern) use ($cutoff) {
            $patternTime = \Carbon\Carbon::parse($pattern['time']);
            return $patternTime->isAfter($cutoff);
        });

        Cache::put($usageKey, array_slice($patterns, -500), now()->addHours(48)); // Keep last 500 entries

        // Analyze patterns for suspicious indicators
        $score = 0;
        
        // Different IP ranges suggest multiple installations
        $uniqueIPRanges = count(array_unique(array_column($patterns, 'ip_range')));
        if ($uniqueIPRanges > 2) {
            $score += 30;
        }

        // Different user agents suggest different clients
        $uniqueUserAgents = count(array_unique(array_column($patterns, 'user_agent')));
        if ($uniqueUserAgents > 5) {
            $score += 25;
        }

        // Access patterns indicating demo/trial behavior
        $hourDistribution = array_count_values(array_column($patterns, 'time'));
        $unusualHours = count(array_filter($hourDistribution, function($count) {
            return $count > 100; // More than 100 requests in single hour
        }));
        
        if ($unusualHours > 0) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Analyze deployment patterns
     */
    private function analyzeDeploymentPatterns(): int
    {
        $score = 0;
        
        // Check if application has been downloaded/moved recently
        $installFingerprint = app(\Acecoderz\LicenseManager\LicenseManager::class)->generateHardwareFingerprint();
        $storedFingerprint = Cache::get('original_fingerprint_' . md5(config('license-manager.license_key')));
        
        if (!$storedFingerprint) {
            // First time, store current fingerprint
            Cache::put('original_fingerprint_' . md5(config('license-manager.license_key')), $installFingerprint, now()->addYears(1));
            $score += 0; // Not suspicious on first run
        } else {
            // Check if fingerprint changed significantly
            $similarity = similar_text($storedFingerprint, $installFingerprint, $percent);
            if ($percent < 85) {
                $score += 40; // High suspicion - significant hardware change
                
                Log::channel('security')->warning('Significant hardware fingerprint change', [
                    'old_fingerprint' => substr($storedFingerprint, 0, 32) . '...',
                    'new_fingerprint' => substr($installFingerprint, 0, 32) . '...',
                    'similarity' => $percent,
                ]);
            }
        }

        return $score;
    }

    /**
     * Detect unauthorized code modifications
     */
    private function detectCodeModifications(): int
    {
        $score = 0;
        
        // List of critical files that shouldn't be modified
        $criticalFiles = [
            'app/Http/Middleware',
            'app/Http/Kernel.php',
            'config/license-manager.php',
            'vendor/acecoderz/license-manager',
        ];

        foreach ($criticalFiles as $filePath) {
            $fullPath = base_path($filePath);
            if (file_exists($fullPath)) {
                $currentHash = hash_file('sha256', $fullPath);
                $storedHash = Cache::get("file_hash_{$filePath}");
                
                if (!$storedHash) {
                    // Store initial hash
                    Cache::put("file_hash_{$filePath}", $currentHash, now()->addYears(1));
                } elseif ($storedHash !== $currentHash) {
                    $score += 25; // High suspicion - file modification
                    
                    Log::channel('security')->critical('Unauthorized file modification detected', [
                        'file' => $filePath,
                        'path' => $fullPath,
                    ]);
                }
            }
        }

        return $score;
    }

    /**
     * Analyze network behavior for suspicious patterns
     */
    private function analyzeNetworkBehavior(): int
    {
        $score = 0;
        
        // Check for VPN/Proxy usage patterns
        $ip = request()->ip();
        $ipData = Cache::get("ip_data_{$ip}");
        
        if (!$ipData) {
            // Simple IP analysis (could be enhanced with external services)
            $ipData = [
                'first_seen' => now(),
                'request_count' => 0,
                'is_vpn_suspicious' => $this->detectVPNSuspicious($ip),
            ];
        }
        
        $ipData['request_count']++;
        Cache::put("ip_data_{$ip}", $ipData, now()->addDays(7));

        // VPN/Proxy detection (basic heuristics)
        if ($ipData['is_vpn_suspicious']) {
            $score += 15;
        }

        // Rapid request patterns suggesting automated tools
        if ($ipData['request_count'] > 1000) {
            $score += 20;
        }

        return $score;
    }

    /**
     * Check for installation clustering (multiple installations in same area)
     */
    private function checkInstallationClustering(): int
    {
        $geoKey = $this->getApproximateGeoLocation(request()->ip());
        $clusterKey = "geo_cluster_{$geoKey}";
        
        $installations = Cache::get($clusterKey, []);
        $currentInstallation = md5(config('license-manager.license_key') . config('license-manager.client_id'));
        
        if (!in_array($currentInstallation, $installations)) {
            $installations[] = $currentInstallation;
            Cache::put($clusterKey, $installations, now()->addDays(30));
        }

        // Too many installations in same geographic area
        $maxAllowedInCluster = config('license-manager.anti_reselling.max_per_geo', 3);
        if (count($installations) > $maxAllowedInCluster) {
            return 35; // Suspicious clustering
        }

        return count($installations) > 1 ? 10 : 0;
    }

    /**
     * Calculate overall suspicious score
     */
    private function calculateSuspiciousScore(array $indicators): int
    {
        $totalScore = array_sum($indicators);
        
        // Cap at 100
        return min($totalScore, 100);
    }

    /**
     * Handle potentially suspicious activity
     */
    private function handlePotentiallySuspiciousActivity(array $indicators, int $score): void
    {
        // Record incident
        Log::channel('security')->alert('Potentially suspicious activity detected', [
            'license_key' => config('license-manager.license_key'),
            'client_id' => config('license-manager.client_id'),
            'domain' => request()->getHost(),
            'ip' => request()->ip(),
            'score' => $score,
            'indicators' => $indicators,
            'timestamp' => now(),
        ]);

        // Report to license server
        $this->reportSuspiciousActivity($score, $indicators);

        // Trigger additional security measures
        $this->triggerSecurityMeasures($score);
    }

    /**
     * Report suspicious activity to license server
     */
    private function reportSuspiciousActivity(int $score, array $indicators): void
    {
        try {
            $licenseServer = config('license-manager.license_server');
            $apiToken = config('license-manager.api_token');

            Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
            ])->post("{$licenseServer}/api/report-suspicious", [
                'license_key' => config('license-manager.license_key'),
                'client_id' => config('license-manager.client_id'),
                'score' => $score,
                'indicators' => $indicators,
                'domain' => request()->getHost(),
                'ip' => request()->ip(),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to report suspicious activity', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Trigger additional security measures
     */
    private function triggerSecurityMeasures(int $score): void
    {
        // Higher scores trigger more aggressive measures
        if ($score >= 90) {
            // Immediate cache block for this installation
            Cache::put('security_block_' . md5(config('license-manager.license_key')), true, now()->addHours(24));
            
            // Force license server validation
            Cache::forget('license_valid_' . md5(config('license-manager.license_key')));
            
        } elseif ($score >= 75) {
            // Reduce cache duration for frequent validation
            Cache::put('high_attention_' . md5(config('license-manager.license_key')), true, now()->addHours(12));
        }
    }

    /**
     * Helper methods
     */
    private function getIPRange(string $ip): string
    {
        // Return first 3 octets for IP range identification
        $parts = explode('.', $ip);
        return implode('.', array_slice($parts, 0, 3));
    }

    private function generateSessionFingerprint(): string
    {
        return hash('sha256', implode('|', [
            request()->ip(),
            request()->header('User-Agent'),
            request()->header('Accept-Language'),
            request()->header('Accept-Encoding'),
        ]));
    }

    private function detectVPNSuspicious(string $ip): bool
    {
        // Basic heuristic - could be enhanced with external VPN detection services
        // Check for known VPN IP ranges or unusual patterns
        $privateRanges = [
            '10.',
            '172.16.',
            '172.17.',
            '172.18.',
            '172.19.',
            '172.20.',
            '172.21.',
            '172.22.',
            '172.23.',
            '172.24.',
            '172.25.',
            '172.26.',
            '172.27.',
            '172.28.',
            '172.29.',
            '172.30.',
            '172.31.',
            '192.168.',
        ];

        // Non-private IPs might be VPNs (heuristic)
        foreach ($privateRanges as $range) {
            if (str_starts_with($ip, $range)) {
                return false;
            }
        }

        return true; // Potential VPN/Proxy
    }

    private function getApproximateGeoLocation(string $ip): string
    {
        // Simple geo approximation based on IP patterns
        // Could be enhanced with geo IP services
        $parts = explode('.', $ip);
        
        if (count($parts) >= 3) {
            // Use first 3 octets as geographic approximation
            return implode('.', array_slice($parts, 0, 2)) . '.X';
        }
        
        return 'unknown';
    }
}
