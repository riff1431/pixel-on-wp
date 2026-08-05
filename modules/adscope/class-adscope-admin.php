<?php
/**
 * Admin Dashboard UI and AI Intelligence Engine for AdScope.
 *
 * @package    PixelOnWP\Modules\Adscope
 * @since      1.0.0
 */

namespace PixelOnWP\Modules\Adscope;

if (!defined('ABSPATH')) {
    exit;
}

class Adscope_Admin {

    public function __construct() {
        // Run at priority 99 to ensure the parent menu 'pixel-on-wp' is already registered
        add_action('admin_menu', [$this, 'add_plugin_admin_menu'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_styles'], 99);
        
        // Handle AJAX requests from dashboard
        add_action('wp_ajax_pixelonwp_adscope_insights', [$this, 'get_insights']);
        add_action('wp_ajax_pixelonwp_adscope_clear_history', [$this, 'clear_history']);
        add_action('wp_ajax_pixelonwp_adscope_reporting', [$this, 'get_reporting']);
    }

    /**
     * Register the submenu.
     */
    public function add_plugin_admin_menu() {
        add_submenu_page(
            'pixel-on-wp', // Parent slug
            __('AdScope AI Ads', 'pixel-on-wp'), // Page title
            __('AdScope AI Ads', 'pixel-on-wp'), // Menu title
            'manage_options', // Capability
            'wpt-adscope', // Menu slug
            [$this, 'display_plugin_setup_page'], // Callback
            2 // Position (above Settings and Diagnostics)
        );
    }

    /**
     * Enqueue Admin Styles and Scripts just for this page.
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'wpt-adscope') === false) {
            return;
        }

        wp_enqueue_style(
            'wpt-adscope-css',
            plugins_url('assets/css/adscope.css', __FILE__),
            [],
            '1.0.0',
            'all'
        );

        wp_enqueue_script(
            'wpt-adscope-app',
            plugins_url('assets/js/adscope-app.js', __FILE__),
            [],
            '1.0.0',
            true
        );

        wp_localize_script(
            'wpt-adscope-app',
            'wptAdscopeAdminVars',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('pixelonwp_adscope_admin_nonce')
            ]
        );
    }

    /**
     * Display the AdScope Dashboard container.
     */
    public function display_plugin_setup_page() {
        ?>
        <div class="wrap">
            <div id="wpt-adscope-app">
                <div style="padding: 40px; text-align: center; color: #6b7280;">Loading AdScope AI Engine...</div>
            </div>
        </div>
        <?php
    }

    /**
     * Get insights data for the dashboard.
     */
    public function get_insights() {
        check_ajax_referer('pixelonwp_adscope_admin_nonce', 'nonce');

        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_adscope_logs';
        
        // Ensure table exists before querying
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
             wp_send_json_success([
                'kpis' => ['total_ips' => 0, 'active_regions' => 0, 'top_source' => 'N/A', 'conversion_rate' => '0%'],
                'live_logs' => [],
                'blueprint' => [
                    'interests' => ['Wait for more data'],
                    'demographics' => 'Wait for more data',
                    'device_shift' => 'Wait for more data',
                    'strategy' => 'Gathering telemetry...',
                    'platform' => 'Listening to pixels...'
                ]
            ]);
            return;
        }

        $total_ips = $wpdb->get_var("SELECT COUNT(DISTINCT ip_address) FROM {$table_name}");
        $active_regions = $wpdb->get_var("SELECT COUNT(DISTINCT region) FROM {$table_name} WHERE region != 'Unknown'");
        
        // Find top source
        $top_source = $wpdb->get_row("SELECT utm_source, COUNT(*) as cnt FROM {$table_name} WHERE utm_source != '' GROUP BY utm_source ORDER BY cnt DESC LIMIT 1");
        $top_source_name = $top_source ? ucfirst(strtolower($top_source->utm_source)) : 'N/A';

        // Conversion rate (mock logic: purchase / page_view)
        $purchases = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE event_type = 'purchase'");
        $page_views = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE event_type = 'page_view'");
        $add_to_carts = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE event_type = 'add_to_cart'");
        $conversion_rate = ($page_views > 0) ? round(($purchases / $page_views) * 100, 2) . '%' : '0%';

        // Get recent logs (now including event_type)
        $live_logs = $wpdb->get_results("SELECT ip_address, city, isp, device, utm_source, event_type, created_at FROM {$table_name} ORDER BY created_at DESC LIMIT 20", ARRAY_A);

        // Analyze for AI Blueprint
        $top_device = $wpdb->get_var("SELECT device FROM {$table_name} WHERE event_type = 'purchase' GROUP BY device ORDER BY COUNT(*) DESC LIMIT 1");
        if (!$top_device) $top_device = $wpdb->get_var("SELECT device FROM {$table_name} GROUP BY device ORDER BY COUNT(*) DESC LIMIT 1");
        
        $top_city = $wpdb->get_var("SELECT city FROM {$table_name} WHERE event_type = 'purchase' GROUP BY city ORDER BY COUNT(*) DESC LIMIT 1");
        if (!$top_city || $top_city === 'Unknown') $top_city = 'major cities';
        
        $device_shift = $top_device ? "Shift 70% budget to " . ucfirst($top_device) . " users." : "Maintain balanced device budget.";
        
        // Dynamic funnel analysis
        $demographics = "Target 18-35 age group in {$top_city}.";
        $strategy = "Run Brand Awareness & Traffic campaigns to build initial audience.";
        $recommended_platform = "Meta (Facebook/Instagram)";

        if ($purchases > $add_to_carts && $purchases > 0) {
            $strategy = "Scale your winning ads! Use Conversion campaigns optimized for Purchases.";
            $recommended_platform = "Meta (CAPI) & Google Analytics 4";
        } elseif ($add_to_carts > 0 && $purchases == 0) {
            $demographics = "High Cart Abandonment! Retarget users in {$top_city}.";
            $strategy = "Launch Retargeting/Catalog Sales offering a 10% discount to cart abandoners.";
            $recommended_platform = "Meta (Facebook) & TikTok Retargeting";
        } elseif ($add_to_carts > 0 && $purchases > 0) {
            $demographics = "Healthy Funnel. Scale Lookalike Audiences in {$top_city}.";
            $strategy = "Use Value-based Optimization (VBO) and Create 1% Lookalike Audiences from Purchasers.";
            $recommended_platform = "Meta (Advantage+ Shopping) & TikTok (Value)";
        } elseif ($page_views > 10 && $add_to_carts == 0) {
            $demographics = "Low Engagement in {$top_city}. Target broader interests.";
            $strategy = "Review Landing Page UX. Run Video Views or Engagement campaigns to warm up audience.";
            $recommended_platform = "TikTok (TopView) & Instagram Reels";
        }

        $interests = [];
        if ($add_to_carts > 0 && $purchases == 0) {
            $interests = ['Cart Abandoners', 'Website Visitors (30d)'];
        } elseif (strtolower($top_source_name) === 'facebook' || strtolower($top_source_name) === 'meta') {
            $interests = ['Online Shopping', 'Engaged Shoppers'];
        } else if (strtolower($top_source_name) === 'tiktok') {
            $interests = ['Viral Trends', 'TikTok Made Me Buy It'];
        } else {
            $interests = ['General Shopping', 'Fashion & Tech'];
        }

        $blueprint = [
            'interests' => $interests,
            'demographics' => $demographics,
            'device_shift' => $device_shift,
            'strategy' => $strategy,
            'platform' => $recommended_platform
        ];

        wp_send_json_success([
            'kpis' => [
                'total_ips' => number_format_i18n($total_ips),
                'active_regions' => number_format_i18n($active_regions),
                'top_source' => $top_source_name,
                'conversion_rate' => $conversion_rate
            ],
            'live_logs' => $live_logs,
            'blueprint' => $blueprint
        ]);
    }

    /**
     * Clear all AdScope tracking history.
     */
    public function clear_history() {
        check_ajax_referer('pixelonwp_adscope_admin_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_adscope_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
            $wpdb->query("TRUNCATE TABLE {$table_name}");
        }

        wp_send_json_success('History cleared successfully.');
    }

    /**
     * Get advanced reporting data.
     */
    public function get_reporting() {
        check_ajax_referer('pixelonwp_adscope_admin_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_adscope_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            wp_send_json_error('Table not found.');
        }

        // Get advanced counts
        $total_events = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
        $total_pixels = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE event_type != 'page_view'");
        $total_datalayer = $total_pixels; // Simplified assumption for frontend

        // Group by Location
        $locations = $wpdb->get_results("SELECT city, region, COUNT(*) as cnt FROM {$table_name} GROUP BY city, region ORDER BY cnt DESC LIMIT 5");
        
        // Group by Source/Interest
        $sources = $wpdb->get_results("SELECT utm_source, COUNT(*) as cnt FROM {$table_name} WHERE utm_source != '' GROUP BY utm_source ORDER BY cnt DESC LIMIT 5");

        wp_send_json_success([
            'total_events' => $total_events,
            'total_pixels' => $total_pixels,
            'total_datalayer' => $total_datalayer,
            'locations' => $locations,
            'sources' => $sources
        ]);
    }
}
