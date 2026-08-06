<?php
/**
 * AI Engine Class for generating Ad strategies via AI providers.
 *
 * Supports Gemini, ChatGPT, and fallback dummy data.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_AI_Engine
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=';

    public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
    {
        // Tracker sync endpoints
        $loader->add_action('wp_ajax_nopriv_pixelonwp_sync_visitor', $this, 'sync_visitor_data');
        $loader->add_action('wp_ajax_pixelonwp_sync_visitor', $this, 'sync_visitor_data');

        // Dashboard AI UI endpoints
        $loader->add_action('wp_ajax_pixelonwp_get_ai_insights', $this, 'get_ai_insights');
        


        // AI API Configuration endpoints
        $loader->add_action('wp_ajax_pixelonwp_save_ai_api_keys', $this, 'save_ai_api_keys');
        $loader->add_action('wp_ajax_pixelonwp_get_ai_api_keys', $this, 'get_ai_api_keys');
        $loader->add_action('wp_ajax_pixelonwp_set_active_provider', $this, 'set_active_provider');
        $loader->add_action('wp_ajax_pixelonwp_test_ai_connection', $this, 'test_ai_connection');
    }

    public function sync_visitor_data(): void
    {
        // Optional nonce check since it's tracking public data
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'pixelonwp_tracker_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $this->ensure_table_exists();

        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_visitor_intelligence';

        $visitor_hash = isset($_POST['visitor_hash']) ? sanitize_text_field(wp_unslash($_POST['visitor_hash'])) : '';
        if (empty($visitor_hash)) {
            wp_send_json_error(['message' => 'No hash']);
        }

        $ip_address = $this->get_client_ip();
        
        $device_context = isset($_POST['device_context']) ? sanitize_text_field(wp_unslash($_POST['device_context'])) : '{}';
        $activity_log = isset($_POST['activity_log']) ? wp_unslash($_POST['activity_log']) : '{}'; // Keep JSON

        // Check if visitor exists
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id, location_info FROM {$table_name} WHERE visitor_hash = %s", $visitor_hash));

        $location_info = '{}';
        if ($existing && !empty($existing->location_info) && $existing->location_info !== '{}') {
            $location_info = $existing->location_info;
        } else {
            // Fetch geo if missing, with cache and short timeout
            $transient_key = 'pixelonwp_geo_' . md5($ip_address);
            $cached_geo = get_transient($transient_key);
            
            if ($cached_geo === 'failed') {
                $location_info = '{}';
            } elseif ($cached_geo) {
                $location_info = $cached_geo;
            } else {
                $geo = wp_remote_get("http://ip-api.com/json/{$ip_address}", ['timeout' => 1.5]);
                if (!is_wp_error($geo) && wp_remote_retrieve_response_code($geo) === 200) {
                    $location_info = wp_remote_retrieve_body($geo);
                    set_transient($transient_key, $location_info, 24 * HOUR_IN_SECONDS);
                } else {
                    set_transient($transient_key, 'failed', 24 * HOUR_IN_SECONDS);
                }
            }
        }

        $data = [
            'visitor_hash' => $visitor_hash,
            'ip_address' => $ip_address,
            'location_info' => $location_info,
            'device_context' => $device_context,
            'activity_log' => $activity_log,
            'last_active' => current_time('mysql', true)
        ];

        $format = ['%s', '%s', '%s', '%s', '%s', '%s'];

        if ($existing) {
            $wpdb->update($table_name, $data, ['id' => $existing->id], $format, ['%d']);
        } else {
            $wpdb->insert($table_name, $data, $format);
        }

        wp_send_json_success();
    }

    public function get_ai_insights(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // Check Cache
        $cached_insights = get_transient('pixelonwp_ai_insights_cache');
        if (false !== $cached_insights) {
            wp_send_json_success($cached_insights);
        }

        $this->ensure_table_exists();

        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_visitor_intelligence';

        $recent_visitors = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY last_active DESC LIMIT 100", ARRAY_A);

        if (empty($recent_visitors)) {
            wp_send_json_error(['message' => 'No visitor data available yet.']);
        }

        $active_count = 0;
        $five_mins_ago = strtotime('-5 minutes');
        foreach ($recent_visitors as $v) {
            if (strtotime($v['last_active']) > $five_mins_ago) {
                $active_count++;
            }
        }

        // Check if this is dummy data (mock visitors)
        $is_dummy = false;
        if (!empty($recent_visitors[0]['visitor_hash']) && strpos($recent_visitors[0]['visitor_hash'], 'mock_visitor_') === 0) {
            $is_dummy = true;
        }

        // Compile payload
        $payload_data = [
            'visitor_count' => count($recent_visitors),
            'sample_data' => array_slice($recent_visitors, 0, 10) // limit context size
        ];
        
        $prompt = "You are a Principal Ad Strategist AI. Analyze the following recent website visitor data: " . wp_json_encode($payload_data) . "
        Return ONLY a strict JSON object with this exact structure:
        {
            \"live_stats\": {\"top_search\": \"(str)\", \"bounce_rate\": \"(str percentage)\"},
            \"meta\": {\"audience\": \"(str)\", \"ad_type\": \"(str)\", \"hook\": \"(str)\", \"cta\": \"(str)\"},
            \"tiktok\": {\"concept\": \"(str script 0-15s)\", \"audio\": \"(str style)\", \"visual\": \"(str)\", \"demographics\": \"(str)\"},
            \"google\": {\"keywords\": \"(str comma separated)\", \"pmax\": \"(str plan)\", \"bidding\": \"(str angle)\"}
        }";

        // Use AI Provider router (User Gemini → User ChatGPT → Inbuilt → null)
        $ai_json = PixelOnWP_AI_Provider::generate($prompt, 0.4);

        if ($ai_json) {
            $ai_json['live_stats']['active_visitors'] = $active_count;
            $ai_json['is_demo'] = $is_dummy;
            set_transient('pixelonwp_ai_insights_cache', $ai_json, 60);
            wp_send_json_success($ai_json);
        }

        // All AI providers failed — return error
        wp_send_json_error(['message' => 'AI insights generation failed.']);
    }

    /**
     * Get pre-built dummy insights for when AI APIs are unavailable.
     */
    private function get_dummy_insights(int $active_count, bool $is_dummy_data = true): array
    {
        return [
            'is_demo' => true,
            'live_stats' => [
                'active_visitors' => $active_count > 0 ? $active_count : 12,
                'top_search' => 'gaming laptop',
                'bounce_rate' => '34%'
            ],
            'meta' => [
                'audience' => 'Tech-savvy millennials (25-34) interested in gaming & electronics',
                'ad_type' => 'Carousel Ads + Dynamic Product Ads (DPA)',
                'hook' => 'Level up your setup — Premium gear starting at $199. Limited stock alert!',
                'cta' => 'Shop Now — Free Shipping Today'
            ],
            'tiktok' => [
                'concept' => 'Open with POV: unboxing a sleek gaming laptop. Quick cuts showing RGB keyboard, game loading, reaction face. End with price flash + CTA overlay.',
                'audio' => 'Trending electronic beat with bass drop on product reveal',
                'visual' => 'Dark aesthetic, neon RGB lighting, close-up macro shots of product details',
                'demographics' => 'Males 18-34, Interest: Gaming, Tech, Gadgets — Tier 1 countries'
            ],
            'google' => [
                'keywords' => 'best gaming laptop 2024, affordable gaming setup, mechanical keyboard deals, smartwatch under 200',
                'pmax' => 'Performance Max campaign targeting Shopping + Search + Display. Focus on high-intent converters with remarketing lists.',
                'bidding' => 'Target ROAS at 400% with enhanced CPC fallback. Start with $20/day budget, scale on 2x ROAS.'
            ]
        ];
    }



    /**
     * Save user-provided AI API keys.
     */
    public function save_ai_api_keys(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $gemini_key = isset($_POST['gemini_key']) ? sanitize_text_field(wp_unslash($_POST['gemini_key'])) : '';
        $chatgpt_key = isset($_POST['chatgpt_key']) ? sanitize_text_field(wp_unslash($_POST['chatgpt_key'])) : '';
        $openrouter_key = isset($_POST['openrouter_key']) ? sanitize_text_field(wp_unslash($_POST['openrouter_key'])) : '';

        update_option('pixelonwp_gemini_api_key', $gemini_key);
        update_option('pixelonwp_chatgpt_api_key', $chatgpt_key);
        update_option('pixelonwp_openrouter_api_key', $openrouter_key);

        // Clear AI caches so fresh responses come from the new provider
        delete_transient('pixelonwp_ai_insights_cache');
        delete_transient('pixelonwp_ai_search_demand_cache');
        delete_transient('pixelonwp_ai_fraud_cache');

        wp_send_json_success([
            'message' => 'AI API keys saved successfully.',
            'provider_status' => PixelOnWP_AI_Provider::get_status()
        ]);
    }

    /**
     * Get current AI API key configuration.
     */
    public function get_ai_api_keys(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // Return keys (admin only)
        $gemini_key = get_option('pixelonwp_gemini_api_key', '');
        $chatgpt_key = get_option('pixelonwp_chatgpt_api_key', '');
        $openrouter_key = get_option('pixelonwp_openrouter_api_key', '');

        wp_send_json_success([
            'gemini_key' => $gemini_key,
            'chatgpt_key' => $chatgpt_key,
            'openrouter_key' => $openrouter_key,
            'provider_status' => PixelOnWP_AI_Provider::get_status()
        ]);
    }

    /**
     * Dynamically update active provider setting via AJAX.
     */
    public function set_active_provider(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $active_provider = isset($_POST['active_provider']) ? sanitize_text_field(wp_unslash($_POST['active_provider'])) : 'inbuilt';
        
        if (in_array($active_provider, ['gemini', 'chatgpt', 'openrouter', 'inbuilt'], true)) {
            update_option('pixelonwp_active_provider', $active_provider);
            
            // Clear AI caches so fresh responses come from the new provider
            delete_transient('pixelonwp_ai_insights_cache');
            delete_transient('pixelonwp_ai_search_demand_cache');
            delete_transient('pixelonwp_ai_fraud_cache');

            wp_send_json_success([
                'message' => 'Active provider updated successfully.',
                'provider_status' => PixelOnWP_AI_Provider::get_status()
            ]);
        }

        wp_send_json_error(['message' => 'Invalid provider selection.']);
    }



    private function get_client_ip(): string
    {
        $ip = '127.0.0.1';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = explode(',', $ip)[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        return trim($ip);
    }

    private function ensure_table_exists(): void
    {
        if (get_option('pixelonwp_ai_table_created') === '1') {
            return;
        }

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
        }
        
        update_option('pixelonwp_ai_table_created', '1');
    }

    /**
     * Test the connection for a specific AI provider dynamically.
     */
    public function test_ai_connection(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

        if (empty($provider)) {
            wp_send_json_error(['message' => 'Provider not specified.']);
        }

        // Lightweight connection verification prompt
        $prompt = "Say 'Hello Connection!' in exactly 2 words.";
        $result = null;
        $error_detail = '';

        if ($provider === 'gemini') {
            PixelOnWP_Gemini_Client::$last_error = '';
            $result = PixelOnWP_Gemini_Client::generate_with_key($api_key, $prompt, 0.4, false, 15);
            $error_detail = PixelOnWP_Gemini_Client::$last_error;
        } elseif ($provider === 'chatgpt') {
            PixelOnWP_ChatGPT_Client::$last_error = '';
            $result = PixelOnWP_ChatGPT_Client::generate($prompt, 0.4, false, 15, $api_key);
            $error_detail = PixelOnWP_ChatGPT_Client::$last_error;
        } elseif ($provider === 'openrouter') {
            PixelOnWP_OpenRouter_Client::$last_error = '';
            $result = PixelOnWP_OpenRouter_Client::generate($prompt, 0.4, false, 25, $api_key);
            $error_detail = PixelOnWP_OpenRouter_Client::$last_error;
        } else {
            wp_send_json_error(['message' => 'Invalid provider specified.']);
        }

        if ($result) {
            $resp_text = isset($result['raw_text']) ? $result['raw_text'] : (is_array($result) ? json_encode($result) : $result);
            wp_send_json_success([
                'message' => 'Connection successful!',
                'response' => trim(strip_tags($resp_text))
            ]);
        }

        // Attempt to parse structured error message for clean display
        $clean_error = 'Unknown API response error.';
        if (!empty($error_detail)) {
            $clean_error = $error_detail;
            
            // Try to extract nested JSON error messages from raw HTTP responses
            if (preg_match('/HTTP \d+: (\{.*\})/s', $error_detail, $matches)) {
                $err_json = json_decode($matches[1], true);
                if (isset($err_json['error']['message'])) {
                    $clean_error = $err_json['error']['message'];
                }
            }
        }

        wp_send_json_error([
            'message' => $clean_error
        ]);
    }
}
