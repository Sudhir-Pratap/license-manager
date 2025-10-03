<?php

namespace Acecoderz\LicenseManager\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WatermarkingService
{
    /**
     * Generate invisible watermark for client identification
     */
    public function generateClientWatermark(string $clientId, string $pageContent = ''): string
    {
        if (!config('license-manager.code_protection.watermarking', true)) {
            return $pageContent;
        }

        $watermark = $this->createWatermark($clientId);
        
        $this->embedWatermarkInContent($watermark, $pageContent);
        
        $this->logWatermarkActivity($clientId, $watermark);
        
        return $pageContent;
    }

    /**
     * Create unique watermark for this client
     */
    private function createWatermark(string $clientId): string
    {
        $seed = hash('sha256', $clientId . config('license-manager.license_key') . date('Y-m-d'));
        
        // Create invisible watermark using Unicode zero-width characters
        $watermarkChars = [
            "\u{200B}", // Zero Width Space
            "\u{200C}", // Zero Width Non-Joiner
            "\u{200D}", // Zero Width Joiner
            "\u{2060}", // Word Joiner
        ];
        
        $watermark = '';
        for ($i = 0; $i < strlen($seed); $i++) {
            $charIndex = hexdec($seed[$i]) % count($watermarkChars);
            $watermark .= $watermarkChars[$charIndex];
        }
        
        return base64_encode($watermark);
    }

    /**
     * Embed watermark in page content
     */
    private function embedWatermarkInContent(string $watermark, string &$content): void
    {
        // Embed watermark in various places
        $this->embedInHTMLComments($watermark, $content);
        $this->embedInJavaScript($watermark, $content);
        $this->embedInCSS($watermark, $content);
        $this->embedInMetaTags($watermark, $content);
    }

    /**
     * Embed watermark in HTML comments
     */
    private function embedInHTMLComments(string $watermark, string &$content): void
    {
        // Insert in HTML comments (invisible but trackable)
        $comment = "<!-- " . substr($watermark, 0, 20) . " -->";
        
        // Insert after <head> or before </head>
        if (strpos($content, '<head>') !== false) {
            $content = str_replace('<head>', "<head>\n{$comment}", $content);
        } elseif (strpos($content, '</head>') !== false) {
            $content = str_replace('</head>', "{$comment}\n</head>", $content);
        }
    }

    /**
     * Embed watermark in JavaScript
     */
    private function embedInJavaScript(string $watermark, string &$content): void
    {
        // Add watermark as JavaScript variable
        $jsWatermark = 'var _WM = "' . substr($watermark, 16, 24) . '";';
        
        // Insert before closing </body> tag
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', "<script>{$jsWatermark}</script>\n</body>", $content);
        }
    }

    /**
     * Embed watermark in CSS
     */
    private function embedInCSS(string $watermark, string &$content): void
    {
        // Add watermark as CSS comment
        $cssWatermark = "/* " . substr($watermark, 32, 16) . " */";
        
        $stylePattern = '/(<style[^>]*>)(.*?)(<\/style>)/is';
        $content = preg_replace($stylePattern, "$1{$cssWatermark}\n$2\n{$cssWatermark}$3", $content, 1);
    }

    /**
     * Embed watermark in meta tags
     */
    private function embedInMetaTags(string $watermark, string &$content): void
    {
        // Add invisible meta tag
        $metaTag = '<meta name="wm" content="' . substr($watermark, 0, 12) . '">';
        
        // Insert after <head> or before </head>
        if (strpos($content, '<head>') !== false) {
            $content = str_replace('<head>', "<head>\n{$metaTag}", $content);
        } elseif (strpos($content, '</head>') !== false) {
            $content = str_replace('</head>', "{$metaTag}\n</head>", $content);
        }
    }

    /**
     * Add runtime integrity checks
     */
    public function addRuntimeChecks(string &$content): void
    {
        if (!config('license-manager.code_protection.runtime_checks', true)) {
            return;
        }

        $checksJavaScript = $this->generateIntegrityCheckScript();
        
        // Insert before closing </body>
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', "<script>\n{$checksJavaScript}\n</script>\n</body>", $content);
        }
    }

    /**
     * Generate JavaScript integrity check
     */
    private function generateIntegrityCheckScript(): string
    {
        $clientId = config('license-manager.client_id');
        $licenseKey = substr(config('license-manager.license_key'), 0, 8);
        
        return "
(function() {
    'use strict';
    
    // Generate dynamic key for integrity checks
    var dk = function() {
        var a=" . json_encode(str_split($clientId)) . ";
        var b=" . json_encode(str_split($licenseKey)) . ";
        var r=[];
        for(var i=0;i<a.length;i++) { r.push(a[i].charCodeAt(0) ^ b[i % b.length].charCodeAt(0)); }
        return btoa(String.fromCharCode.apply(null, r));
    };
    
    // Perform integrity checks
    var checkIntegrity = function() {
        try {
            // Check for developer tools
            if (typeof console !== 'undefined' && console.firebug) { return false; }
            
            // Check for common debugging tools
            if (typeof debugger !== 'undefined') { 
                try { debugger; } catch(e) { return false; }
            }
            
            // Validate page structure
            var head = document.querySelector('head');
            var meta = head ? head.querySelector('meta[name=\"wm\"]') : null;
            if (!meta || !meta.content) { return false; }
            
            // Time-based validation
            var now = Date.now();
            var stamp = parseInt(meta.content.substring(0, 8), 16);
            if (Math.abs(now - stamp) > 86400000) { return false; }
            
            return true;
        } catch(e) {
            return false;
        }
    };
    
    // Execute checks periodically
    setInterval(function() {
        if (!checkIntegrity()) {
            // Silent reporting to license server
            try {
                var img = new Image();
                img.src = '" . config('license-manager.license_server') . "/api/rt-check?err=' + encodeURIComponent('integrity');
            } catch(e) {}
        }
    }, 30000); // Every 30 seconds
    
})();
";
    }

    /**
     * Add dynamic validation keys
     */
    public function generateDynamicKeys(): array
    {
        if (!config('license-manager.code_protection.dynamic_validation', true)) {
            return [];
        }

        $timestamp = time();
        $clientId = config('license-manager.client_id');
        $licenseKey = config('license-manager.license_key');
        
        // Generate time-based dynamic keys
        $keys = [
            'session_key' => hash('sha256', $clientId . $timestamp . 'session'),
            'validation_token' => hash('sha256', $licenseKey . $timestamp . $clientId),
            'integrity_hash' => hash('sha256', $clientId . $timestamp . 'integrity'),
        ];
        
        // Cache keys with short TTL
        foreach ($keys as $type => $key) {
            Cache::put("dynamic_key_{$type}", $key, now()->addMinutes(15));
        }
        
        return $keys;
    }

    /**
     * Detect potentially modified content
     */
    public function detectContentModification(string $content): bool
    {
        // Check for missing watermarks
        $hasHTMLComment = strpos($content, '<!-- ') !== false && 
                         preg_match('/<!-- [a-zA-Z0-9+\/=]{15,20} -->/', $content);
        
        $hasMetaTag = preg_match('/<meta[^>]*name="wm"[^>]*>/', $content);
        
        $hasJavaScriptVar = strpos($content, 'var _WM = ') !== false;
        
        // All watermarks should be present
        $watermarksPresent = $hasHTMLComment && $hasMetaTag && $hasJavaScriptVar;
        
        if (!$watermarksPresent) {
            Log::channel('security')->warning('Missing watermarks detected', [
                'html_comment' => $hasHTMLComment,
                'meta_tag' => $hasMetaTag,
                'javascript_var' => $hasJavaScriptVar,
                'domain' => request()->getHost(),
            ]);
            
            return true; // Content appears modified
        }
        
        return false;
    }

    /**
     * Log watermark activity
     */
    private function logWatermarkActivity(string $clientId, string $watermark): void
    {
        Log::channel('license')->debug('Watermark applied', [
            'client_id' => $clientId,
            'watermark_hash' => substr($watermark, 0, 16) . '...',
            'domain' => request()->getHost(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Add anti-debugging measures
     */
    public function addAntiDebugProtection(string &$content): void
    {
        if (!config('license-manager.code_protection.anti_debug', true)) {
            return;
        }

        $antiDebugScript = "
<script>
(function() {
    var check = function() {
        if (window.chrome && window.chrome.runtime && window.chrome.runtime.onConnect) {
            // Chrome DevTools detection
(()=>{})();
        }
        
        var start = new Date();
        debugger;
        var end = new Date();
        if (end - start > 100) {
            // Debugger detected - silent exit or modification
            document.body.style.display = 'none';
            setTimeout(() => document.body.style.display = '', 5000);
        }
    };
    
    setInterval(check, 1000);
})();
</script>
";
        
        // Insert before closing </body>
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', "{$antiDebugScript}\n</body>", $content);
        }
    }

    /**
     * Obfuscate sensitive data
     */
    public function obfuscateData(array $data): string
    {
        $key = config('license-manager.license_key');
        $iv = substr(hash('sha256', $key), 0, 16);
        
        $json = json_encode($data);
        $obfuscated = base64_encode(openssl_encrypt($json, 'AES-128-CBC', $key, 0, $iv));
        
        // Add additional encoding layer
        return str_rot13($obfuscated);
    }

    /**
     * Deobfuscate data
     */
    public function deobfuscateData(string $obfuscated): array
    {
        try {
            $key = config('license-manager.license_key');
            $iv = substr(hash('sha256', $key), 0, 16);
            
            $decoded = str_rot13($obfuscated);
            $decrypted = openssl_decrypt(base64_decode($decoded), 'AES-128-CBC', $key, 0, $iv);
            
            return json_decode($decrypted, true) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
