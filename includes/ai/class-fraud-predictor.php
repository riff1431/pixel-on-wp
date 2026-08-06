<?php
/**
 * AI Fraud Predictor Class.
 *
 * @package PixelOnWP\Includes\Ai
 */

namespace PixelOnWP\Includes\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_Fraud_Predictor
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=';

    public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
    {
        $loader->add_action('wp_ajax_pixelonwp_get_fraud_radar', $this, 'get_fraud_radar');
    }

    public function get_fraud_radar(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'PixelOnWP_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        // Check Cache
        $cached_insights = get_transient('pixelonwp_ai_fraud_cache');
        if (false !== $cached_insights) {
            wp_send_json_success($cached_insights);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pixelonwp_visitor_intelligence';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            wp_send_json_error(['message' => 'No visitor traffic data available yet.']);
        }

        // Get last 50 distinct IPs to evaluate
        $visitors = $wpdb->get_results("SELECT ip_address, location_info, device_context, activity_log FROM {$table_name} ORDER BY last_active DESC LIMIT 50", ARRAY_A);

        if (empty($visitors)) {
            wp_send_json_error(['message' => 'No visitor traffic data available yet.']);
        }

        $payload = [];
        foreach ($visitors as $v) {
            $loc = json_decode($v['location_info'], true);
            $dev = json_decode($v['device_context'], true);
            $act = json_decode($v['activity_log'], true);
            
            // Basic heuristics to limit context sent to AI
            $is_proxy = isset($loc['proxy']) && $loc['proxy'];
            $is_hosting = isset($loc['hosting']) && $loc['hosting'];
            
            $payload[] = [
                'ip' => $v['ip_address'],
                'is_proxy' => $is_proxy,
                'is_hosting' => $is_hosting,
                'isp' => $loc['isp'] ?? 'Unknown',
                'device' => $dev['userAgent'] ?? 'Unknown',
                'clicks' => count($act['clicks'] ?? []),
                'searches' => count($act['searches'] ?? []),
                'cart_adds' => count($act['cart_actions'] ?? [])
            ];
        }

        $prompt = "You are a Cybersecurity AI. Analyze these website visitors for fraud/bot behavior: " . wp_json_encode($payload) . "
        Calculate the percentage of safe vs suspicious traffic.
        Identify any specific IP addresses that look like bots (proxies, hosting centers, excessive cart additions without other actions) and provide a short reason.
        Return ONLY a strict JSON object with this exact structure:
        {
            \"safe_percentage\": (int),
            \"suspicious_percentage\": (int),
            \"flagged_ips\": [
                {\"ip\": \"(str)\", \"reason\": \"(str)\", \"risk_score\": \"(int 0-100)\"}
            ]
        }";

        // Use AI Provider router
        $ai_json = PixelOnWP_AI_Provider::generate($prompt, 0.2);

        if ($ai_json) {
            $ai_json['is_demo'] = false;
            set_transient('pixelonwp_ai_fraud_cache', $ai_json, 300);
            wp_send_json_success($ai_json);
        }

        // AI failed — return error
        wp_send_json_error(['message' => 'AI fraud prediction scan failed.']);
    }

    /**
     * Get pre-built dummy fraud radar data.
     */
    private function get_dummy_fraud_data(): array
    {
        return [
            'is_demo' => true,
            'safe_percentage' => 84,
            'suspicious_percentage' => 16,
            'flagged_ips' => [
                [
                    'ip' => '198.51.100.5',
                    'reason' => 'Hosting provider IP (DigitalOcean). 5 rapid cart additions with no searches or page views — likely automated bot.',
                    'risk_score' => 92
                ],
                [
                    'ip' => '203.0.113.15',
                    'reason' => 'Known proxy/VPN exit node. Multiple rapid requests from hosting center ISP.',
                    'risk_score' => 85
                ],
                [
                    'ip' => '185.12.33.2',
                    'reason' => 'Suspicious pattern: 5 cart actions in under 10 seconds, no organic browsing behavior detected.',
                    'risk_score' => 78
                ]
            ]
        ];
    }
}
