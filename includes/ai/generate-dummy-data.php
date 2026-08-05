<?php
/**
 * Standalone script to insert dummy AI data.
 */

$log_file = WP_CONTENT_DIR . '/dummy_data_log.txt';
file_put_contents($log_file, "Starting Dummy Data Generation...\n");

// Load WordPress only if not loaded
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

file_put_contents($log_file, "WordPress loaded.\n", FILE_APPEND);

// 1. Create a dummy WooCommerce product if WooCommerce is active
if (class_exists('WooCommerce')) {
    $existing = wc_get_products(['status' => 'publish', 'limit' => 1]);
    if (empty($existing)) {
        file_put_contents($log_file, "Creating dummy WooCommerce product...\n", FILE_APPEND);
        $product = new WC_Product_Simple();
        $product->set_name('Premium Smartwatch Pro');
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_price('199.99');
        $product->set_regular_price('199.99');
        $product->set_description('An advanced smartwatch with heart rate monitoring, GPS tracking, and a stunning AMOLED display.');
        $product->set_short_description('Track your fitness in style.');
        $product->save();
        file_put_contents($log_file, "Created product ID: " . $product->get_id() . "\n", FILE_APPEND);
    } else {
        file_put_contents($log_file, "WooCommerce products already exist, skipping product creation.\n", FILE_APPEND);
    }
} else {
    file_put_contents($log_file, "WooCommerce not active, skipping product creation.\n", FILE_APPEND);
}

// 2. Insert 50 dummy visitors into the database
global $wpdb;
$table_name = $wpdb->prefix . 'pixelonwp_visitor_intelligence';

if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            visitor_hash VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            location_info JSON NULL,
            device_context JSON NULL,
            activity_log LONGTEXT NULL,
            last_active DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY visitor_hash (visitor_hash),
            KEY last_active (last_active)
        ) {$charset_collate};";
    dbDelta($sql);
    file_put_contents($log_file, "Created table $table_name.\n", FILE_APPEND);
}

if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name) {
    file_put_contents($log_file, "Populating visitor logs in $table_name...\n", FILE_APPEND);
    
    // Clear old transients to force refresh
    delete_transient('pixelonwp_ai_insights_cache');
    delete_transient('pixelonwp_ai_search_cache');
    delete_transient('pixelonwp_ai_fraud_cache');

    // Create realistic fake data
    $ips = [
        '192.168.1.5', '10.0.0.12', '172.16.0.4', // Local-ish
        '45.22.1.9', '185.12.33.2', '103.44.1.20', // Generic Public
        '198.51.100.5', '203.0.113.15', // Bad actors / proxies
    ];
    $devices = ['Mozilla/5.0 (Windows NT 10.0; Win64; x64)', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0)', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)'];
    
    // Keywords for search demand (some high intent)
    $searches = ['gaming laptop', 'running shoes', 'mechanical keyboard', 'smartwatch', 't-shirt black', 'wireless earbuds'];

    for ($i = 0; $i < 50; $i++) {
        $ip = $ips[array_rand($ips)];
        $device = $devices[array_rand($devices)];
        $search = $searches[array_rand($searches)];
        
        $is_suspicious = ($i % 7 === 0);
        $loc = [
            'country' => 'US', 
            'city' => 'New York',
            'proxy' => $is_suspicious,
            'hosting' => $is_suspicious,
            'isp' => $is_suspicious ? 'DigitalOcean LLC' : 'Comcast Cable'
        ];

        // 3 searches per user to build volume
        $activity = [
            'searches' => [
                ['query' => $search, 'timestamp' => time() - rand(100, 1000)],
                ['query' => $searches[array_rand($searches)], 'timestamp' => time() - rand(100, 1000)],
                ['query' => $searches[array_rand($searches)], 'timestamp' => time() - rand(100, 1000)]
            ],
            'clicks' => [],
            'cart_actions' => []
        ];

        if ($is_suspicious) {
            // Rapid cart additions
            for ($j=0; $j<5; $j++) {
                $activity['cart_actions'][] = ['product' => 'spam', 'timestamp' => time()];
            }
        } else {
            // Normal behavior
            if (rand(0,1)) $activity['cart_actions'][] = ['product' => 'shoes', 'timestamp' => time() - rand(50, 200)];
        }

        $wpdb->insert($table_name, [
            'visitor_hash' => 'mock_visitor_' . uniqid() . '_' . $i,
            'ip_address' => $ip,
            'location_info' => wp_json_encode($loc),
            'device_context' => wp_json_encode(['userAgent' => $device]),
            'activity_log' => wp_json_encode($activity),
            'last_active' => current_time('mysql')
        ]);
    }
    
    file_put_contents($log_file, "Inserted 50 visitor logs.\n", FILE_APPEND);
} else {
    file_put_contents($log_file, "Table {$table_name} does not exist.\n", FILE_APPEND);
}

file_put_contents($log_file, "Done.\n", FILE_APPEND);

