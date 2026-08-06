<?php
/**
 * AI Search Analyzer Class.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Search_Analyzer
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=';

    public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
    {
        $loader->add_action('wp_ajax_pixelonwp_analyze_search_demand', $this, 'analyze_search_demand');
    }

    public function analyze_search_demand(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // Check Cache
        $cached_insights = get_transient('pixelonwp_ai_search_demand_cache');
        if (false !== $cached_insights) {
            wp_send_json_success($cached_insights);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_visitor_intelligence';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            wp_send_json_error(['message' => 'No search demand data available yet.']);
        }

        $logs = $wpdb->get_results("SELECT activity_log FROM {$table_name} WHERE activity_log LIKE '%searches%' ORDER BY last_active DESC LIMIT 200", ARRAY_A);
        
        $search_queries = [];
        foreach ($logs as $log_row) {
            $log = json_decode($log_row['activity_log'], true);
            if (!empty($log['searches'])) {
                foreach ($log['searches'] as $s) {
                    if (!empty($s['query'])) {
                        $search_queries[] = strtolower(trim($s['query']));
                    }
                }
            }
        }

        if (empty($search_queries)) {
            wp_send_json_error(['message' => 'No search demand data available yet.']);
        }

        // Count frequencies
        $search_counts = array_count_values($search_queries);
        arsort($search_counts);
        $top_searches = array_slice($search_counts, 0, 15, true);

        // Cross-reference with WooCommerce products
        $catalog_context = [];
        if (class_exists('WooCommerce')) {
            foreach ($top_searches as $term => $count) {
                $args = [
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    's' => $term,
                    'posts_per_page' => 1
                ];
                $query = new \WP_Query($args);
                $found = $query->found_posts > 0;
                $catalog_context[] = [
                    'keyword' => $term,
                    'frequency' => $count,
                    'in_stock' => $found ? 'Yes' : 'No'
                ];
            }
        } else {
            foreach ($top_searches as $term => $count) {
                $catalog_context[] = ['keyword' => $term, 'frequency' => $count, 'in_stock' => 'Unknown'];
            }
        }

        $prompt = "You are an eCommerce Demand Analyst AI. Analyze the following search queries made by visitors and cross-referenced with the store catalog: " . wp_json_encode($catalog_context) . "
        Identify high-frequency searches that return 'No' for in_stock. 
        Return ONLY a strict JSON object with this exact structure:
        {
            \"unmet_demand\": [
                {\"keyword\": \"(str)\", \"frequency\": (int), \"status\": \"(str, e.g. Out of Stock / Unmet)\"}
            ],
            \"recommendation\": \"(str, action-oriented business recommendation based on the missing stock)\"
        }";

        // Use AI Provider router
        $ai_json = PixelOnWP_AI_Provider::generate($prompt, 0.4);

        if ($ai_json) {
            $ai_json['is_demo'] = false;
            set_transient('pixelonwp_ai_search_demand_cache', $ai_json, 3600);
            wp_send_json_success($ai_json);
        }

        // AI failed — return error
        wp_send_json_error(['message' => 'AI search demand analysis failed.']);
    }

    /**
     * Get pre-built dummy search demand data.
     */
    private function get_dummy_search_demand(): array
    {
        return [
            'is_demo' => true,
            'unmet_demand' => [
                ['keyword' => 'gaming laptop', 'frequency' => 47, 'status' => 'Unmet — No matching product'],
                ['keyword' => 'wireless earbuds', 'frequency' => 38, 'status' => 'Unmet — No matching product'],
                ['keyword' => 'mechanical keyboard', 'frequency' => 31, 'status' => 'Unmet — No matching product'],
                ['keyword' => 'running shoes', 'frequency' => 26, 'status' => 'Out of Stock'],
                ['keyword' => 'smartwatch', 'frequency' => 22, 'status' => 'In Stock'],
                ['keyword' => 't-shirt black', 'frequency' => 15, 'status' => 'Unmet — No matching product'],
            ],
            'recommendation' => '🔥 High Priority: Add "gaming laptop" and "wireless earbuds" to your catalog immediately — these represent 85+ combined searches with zero inventory coverage. Consider sourcing "mechanical keyboard" as the third highest demand product. Revenue opportunity estimated at $5,000-15,000/month based on search volume.'
        ];
    }
}
