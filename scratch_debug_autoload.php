<?php
/**
 * Debug script: Run this in the WordPress context to find autoloader issues.
 * Access via: http://pixelonwp.local/wp-content/plugins/pixel-on-wp/scratch_debug_autoload.php
 * (must be run via WP-CLI or similar)
 */

// Load WordPress
require_once dirname(__DIR__, 3) . '/wp-load.php';

echo "<pre>\n";
echo "=== PixelOnWP Autoloader Debug ===\n\n";

// Test autoloader resolution
$test_classes = [
    '\\PixelOnWP\\PixelOnWP_Loader',
    '\\PixelOnWP\\Includes\\Admin\\PixelOnWP_Admin_Menu',
    '\\PixelOnWP\\Includes\\Admin\\PixelOnWP_Admin_Ajax',
    '\\PixelOnWP\\Includes\\Frontend\\PixelOnWP_Frontend',
    '\\PixelOnWP\\Includes\\Controllers\\PixelOnWP_Event_Controller',
    '\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Native_Tracker',
    '\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_TikTok_Tracker',
    '\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Google_Tracker',
    '\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Browser_Tracker',
    '\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Server_Tracker',
    '\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Platform',
    '\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Admin_Options',
    '\\PixelOnWP\\Includes\\Platforms\\GoogleAds\\PixelOnWP_Google_Ads_Platform',
    '\\PixelOnWP\\Includes\\Server\\PixelOnWP_Server_Endpoint',
    '\\PixelOnWP\\Includes\\Diagnostics\\PixelOnWP_Diagnostics',
    '\\PixelOnWP\\Includes\\Helpers\\PixelOnWP_Helper',
    '\\PixelOnWP\\Includes\\Security\\PixelOnWP_Fraud_Prevention',
    '\\PixelOnWP\\Includes\\Ai\\PixelOnWP_AI_Engine',
    '\\PixelOnWP\\Includes\\Licensing\\PixelOnWP_License_Manager',
    '\\PixelOnWP\\Includes\\Cron\\PixelOnWP_Cron_Manager',
    '\\PixelOnWP\\Includes\\Queue\\PixelOnWP_Queue_Processor',
    '\\PixelOnWP\\Includes\\Integrations\\PixelOnWP_GA4',
];

// Simulate the autoloader logic
$plugin_path = plugin_dir_path(__FILE__);

foreach ($test_classes as $class) {
    $prefix = 'PixelOnWP\\';
    $clean_class = ltrim($class, '\\');
    
    if (strncmp($prefix, $clean_class, strlen($prefix)) !== 0) {
        echo "SKIP (wrong prefix): $class\n";
        continue;
    }
    
    $relative_class = substr($clean_class, strlen($prefix));
    $parts = explode('\\', $relative_class);
    $class_name = array_pop($parts);
    $dir_parts = array_map('strtolower', $parts);
    
    $file_name = strtolower(str_replace('_', '-', $class_name));
    $file_name = str_replace('pixelonwp-', '', $file_name);
    $file_name = 'class-' . $file_name . '.php';
    
    $file = rtrim($plugin_path, '/') . '/' . implode('/', $dir_parts) . '/' . $file_name;
    
    $exists = file_exists($file);
    $loaded = class_exists($clean_class, false); // Don't trigger autoload
    
    $status = $exists ? "✓ FILE EXISTS" : "✗ FILE NOT FOUND";
    echo sprintf("%-70s %s\n   Path: %s\n\n", $class, $status, $file);
}

echo "</pre>";
