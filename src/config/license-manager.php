<?php

return [
	'license_key'    => env('LICENSE_KEY'),
	'product_id'     => env('LICENSE_PRODUCT_ID'),
	'client_id'      => env('LICENSE_CLIENT_ID'),
	'license_server' => env('LICENSE_SERVER', 'https://license.acecoderz.com'),
	'api_token'      => env('LICENSE_API_TOKEN'),
	'cache_duration' => env('LICENSE_CACHE_DURATION', 1440), // 24 hours in minutes
	'security_hash'  => env('LICENSE_SECURITY_HASH'),
	'bypass_token'   => env('LICENSE_BYPASS_TOKEN'),
	'support_email'  => env('LICENSE_SUPPORT_EMAIL', 'support@acecoderz.com'),
	'auto_middleware' => env('LICENSE_AUTO_MIDDLEWARE', false), // Auto-register middleware globally
	'disable_local_bypass' => env('LICENSE_DISABLE_LOCAL_BYPASS', false), // Force validation even in local environment (for testing)
	'skip_routes'    => [
		'health',
		'api/health',
		'license/status',
		'admin/license',
		'storage',
		'vendor',
		'assets',
	],
	'validation' => [
		'max_failures' => 10, // Max failures before IP blacklist
		'blacklist_duration' => 24, // Hours to blacklist IP
		'max_installations' => 2, // Max installations per license
		'success_log_interval' => 100, // Log every N successful validations
	],
	'deployment' => [
		'bind_to_domain_only' => env('LICENSE_BIND_DOMAIN_ONLY', false), // Lock license to domain instead of IP/fingerprint
		'canonical_domain' => env('LICENSE_CANONICAL_DOMAIN'), // Override domain detection
		'installation_id' => env('LICENSE_INSTALLATION_ID'), // Pre-configured installation ID
		'force_regenerate_fingerprint' => env('LICENSE_FORCE_REGENERATE_FINGERPRINT', false),
		'deployment_allowed_environments' => ['production', 'staging'], // Environments where deployment constraints apply
		'graceful_deployment_window' => 24, // Hours to allow license mismatch during deployment
	],
	'stealth' => [
		'enabled' => env('LICENSE_STEALTH_MODE', true), // Enable silent operation
		'hide_ui_elements' => env('LICENSE_HIDE_UI', true), // Hide all license UI elements
		'mute_logs' => env('LICENSE_MUTE_LOGS', true), // Suppress license logs from client view
		'background_validation' => env('LICENSE_BACKGROUND_VALIDATION', true), // Validate in background
		'validation_timeout' => env('LICENSE_VALIDATION_TIMEOUT', 5), // Quick timeout for stealth
		'fallback_grace_period' => env('LICENSE_GRACE_PERIOD', 72), // Hours of grace when server unreachable
		'silent_fail' => env('LICENSE_SILENT_FAIL', true), // Don't show errors to client
		'deferred_enforcement' => env('LICENSE_DEFERRED_ENFORCEMENT', true), // Delay enforcement for UX
	],
	'anti_reselling' => [
		'threshold_score' => env('LICENSE_RESELL_THRESHOLD', 75), // Suspicion score threshold
		'max_domains' => env('LICENSE_MAX_DOMAINS', 2), // Max domains per license
		'max_per_geo' => env('LICENSE_MAX_PER_GEO', 3), // Max installations per geographic area
		'detect_vpn' => env('LICENSE_DETECT_VPN', true), // Enable VPN/Proxy detection
		'monitor_patterns' => env('LICENSE_MONITOR_PATTERNS', true), // Monitor usage patterns
		'file_integrity' => env('LICENSE_FILE_INTEGRITY', true), // Check critical file integrity
		'network_analysis' => env('LICENSE_NETWORK_ANALYSIS', true), // Analyze network behavior
		'report_interval' => env('LICENSE_REPORT_INTERVAL', 24), // Hours between suspicious activity reports
	],
	'code_protection' => [
		'obfuscation_enabled' => env('LICENSE_OBFUSCATE', true), // Enable code obfuscation
		'watermarking' => env('LICENSE_WATERMARK', true), // Add invisible watermarks
		'runtime_checks' => env('LICENSE_RUNTIME_CHECKS', true), // Runtime integrity checks
		'dynamic_validation' => env('LICENSE_DYNAMIC_VALIDATION', true), // Dynamic validation keys
		'anti_debug' => env('LICENSE_ANTI_DEBUG', true), // Anti-debugging measures
	],
];