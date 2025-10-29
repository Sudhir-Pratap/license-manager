<?php
/**
 * Quick script to verify LICENSE_SECRET is set correctly
 * Run this from your application root (e.g., agent-panel)
 */

// Adjust this path if needed
$autoloadPath = __DIR__ . '/../../Askpolicy/agent-panel/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    echo "Error: autoload.php not found. Adjust the path in this script.\n";
    exit(1);
}

require $autoloadPath;

$app = require_once dirname($autoloadPath) . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$licenseSecret = env('LICENSE_SECRET');
$appKey = env('APP_KEY');

echo "=== Client (agent-panel) Crypto Key Check ===\n\n";
echo "LICENSE_SECRET: " . ($licenseSecret ? "SET (" . substr($licenseSecret, 0, 15) . "...)" : "NOT SET") . "\n";
echo "APP_KEY: " . ($appKey ? "SET (" . substr($appKey, 0, 15) . "...)" : "NOT SET") . "\n";
echo "Crypto Key Used: " . ($licenseSecret ? "LICENSE_SECRET" : "APP_KEY") . "\n";
echo "Key Preview: " . substr($licenseSecret ?: $appKey, 0, 20) . "...\n\n";

echo "=== For checksum validation ===\n";
echo "Both license-server and agent-panel MUST use the SAME crypto key.\n";
echo "Set LICENSE_SECRET on both .env files with the SAME value.\n";

