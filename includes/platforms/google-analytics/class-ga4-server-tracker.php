<?php
/**
 * GA4 Server Tracker Class (Measurement Protocol API).
 *
 * @package PixelOnWP\Includes\Platforms\GoogleAnalytics
 */

namespace PixelOnWP\Includes\Platforms\GoogleAnalytics;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_GA4_Server_Tracker {

    /**
     * Listen and intercept the pixelonwp_track_event hook.
     *
     * @param string $event_name
     * @param array $event_data
     * @param array $user_data
     * @param string $event_id
     */
    public static function handle_track_event_hook(string $event_name, array $event_data = [], array $user_data = [], string $event_id = ''): void {
        self::dispatch($event_name, $event_id, $event_data, $user_data);
    }

    /**
     * Recursively sanitize all incoming payload data.
     *
     * @param mixed $data
     * @return mixed
     */
    private static function sanitize_data($data) {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[sanitize_key($key)] = self::sanitize_data($value);
            }
            return $sanitized;
        }
        return sanitize_text_field($data);
    }

    /**
     * Queue event to be dispatched server-to-server to GA4 Measurement Protocol.
     *
     * @param string $event_name
     * @param string $event_id
     * @param array $custom_data
     * @param array $user_data
     */
    public static function dispatch(string $event_name, string $event_id = '', array $custom_data = [], array $user_data = []): void {
        $options = PixelOnWP_GA4_Admin_Options::get_options();
        $measurement_id = trim($options['measurement_id'] ?? '');
        $api_secret = trim($options['api_secret'] ?? '');

        // Validation criteria: Measurement ID and API Secret
        if (empty($measurement_id) || empty($api_secret)) {
            return;
        }

        // Filter event execution via pixelonwp_ga4_events_control
        $events_control = get_option('pixelonwp_ga4_events_control', $options['events'] ?? []);
        if (isset($events_control[$event_name])) {
            $val = $events_control[$event_name];
            if ($val === '0' || $val === 0 || $val === 'false' || $val === false || (is_array($val) && empty($val['server']))) {
                return;
            }
        }

        // Sanitize data
        $custom_data = self::sanitize_data($custom_data);
        $user_data = self::sanitize_data($user_data);
        if (empty($event_id)) {
            $event_id = 'evt_' . wp_generate_uuid4();
        }

        // Resolve client_id: Extract from custom_data, fallback to _ga cookie, or UUID
        $client_id = '';
        if (!empty($custom_data['client_id'])) {
            $client_id = $custom_data['client_id'];
        } elseif (isset($_COOKIE['_ga'])) {
            $client_id = preg_replace('/^GA\d\.\d\./', '', $_COOKIE['_ga']);
        }
        if (empty($client_id)) {
            $client_id = wp_generate_uuid4();
        }

        // Resolve session_id: Extract from custom_data, fallback to _ga_<measurement_id> cookie, or default
        $session_id = '';
        if (!empty($custom_data['session_id'])) {
            $session_id = $custom_data['session_id'];
        } else {
            $measurement_id_clean = str_replace('G-', '', $measurement_id);
            $session_cookie_name = '_ga_' . $measurement_id_clean;
            if (isset($_COOKIE[$session_cookie_name])) {
                $parts = explode('.', $_COOKIE[$session_cookie_name]);
                if (count($parts) > 2) {
                    $session_id = $parts[2];
                }
            } elseif (isset($_COOKIE['_ga_sid'])) {
                $session_id = $_COOKIE['_ga_sid'];
            }
        }
        if (empty($session_id)) {
            $session_id = strval(time());
        }

        // Resolve user_id: Logged in WP User or hashed unique ID
        $ga4_user_id = '';
        if (is_user_logged_in()) {
            $ga4_user_id = strval(get_current_user_id());
        } elseif (!empty($user_data['email'])) {
            $ga4_user_id = hash('sha256', $user_data['email']);
        } elseif (!empty($user_data['phone_number'])) {
            $ga4_user_id = hash('sha256', $user_data['phone_number']);
        }

        // Clean parameters list
        unset($custom_data['client_id']);
        unset($custom_data['session_id']);

        // Base/Default Parameters
        $page_location = $custom_data['page_location'] ?? (isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url($_SERVER['REQUEST_URI'] ?? ''));
        $page_title = $custom_data['page_title'] ?? get_bloginfo('name');
        $currency = $custom_data['currency'] ?? (function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD');

        $mapped_event_name = $event_name;
        $event_params = [
            'event_id' => $event_id,
            'session_id' => $session_id,
            'page_location' => $page_location,
            'page_title' => $page_title,
            'currency' => $currency,
            'engagement_time_msec' => '100'
        ];

        // 1. Custom Event Handler Translation
        $custom_events_rules = get_option('pixelonwp_ga4_custom_events', $options['custom_events'] ?? []);
        $is_custom_mapped = false;
        foreach ($custom_events_rules as $rule) {
            if (isset($rule['name']) && $rule['name'] === $event_name) {
                $mapped_event_name = $rule['mapped_name'] ?? $rule['name'];
                if (isset($rule['category'])) $event_params['event_category'] = $rule['category'];
                if (isset($rule['label'])) $event_params['event_label'] = $rule['label'];
                if (isset($rule['value'])) $event_params['value'] = (float)$rule['value'];
                
                // Add any configured custom params
                if (isset($rule['parameters']) && is_array($rule['parameters'])) {
                    foreach ($rule['parameters'] as $p) {
                        if (isset($p['key'])) {
                            $event_params[sanitize_key($p['key'])] = sanitize_text_field($p['value'] ?? '');
                        }
                    }
                }
                $is_custom_mapped = true;
                break;
            }
        }

        // 2. Standard GA4 Taxonomy Parameter Mapping Matrix
        if (!$is_custom_mapped) {
            switch ($event_name) {
                case 'view_item_list':
                case 'select_item':
                    if (isset($custom_data['item_list_id'])) $event_params['item_list_id'] = $custom_data['item_list_id'];
                    if (isset($custom_data['item_list_name'])) $event_params['item_list_name'] = $custom_data['item_list_name'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'view_item':
                case 'add_to_cart':
                case 'remove_from_cart':
                case 'view_cart':
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'begin_checkout':
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['coupon'])) $event_params['coupon'] = $custom_data['coupon'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'add_shipping_info':
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['shipping_tier'])) $event_params['shipping_tier'] = $custom_data['shipping_tier'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'add_payment_info':
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['payment_type'])) $event_params['payment_type'] = $custom_data['payment_type'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'purchase':
                    if (isset($custom_data['transaction_id'])) $event_params['transaction_id'] = $custom_data['transaction_id'];
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['tax'])) $event_params['tax'] = (float)$custom_data['tax'];
                    if (isset($custom_data['shipping'])) $event_params['shipping'] = (float)$custom_data['shipping'];
                    if (isset($custom_data['coupon'])) $event_params['coupon'] = $custom_data['coupon'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'refund':
                    if (isset($custom_data['transaction_id'])) $event_params['transaction_id'] = $custom_data['transaction_id'];
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'view_promotion':
                case 'select_promotion':
                    if (isset($custom_data['promotion_id'])) $event_params['promotion_id'] = $custom_data['promotion_id'];
                    if (isset($custom_data['promotion_name'])) $event_params['promotion_name'] = $custom_data['promotion_name'];
                    if (isset($custom_data['creative_name'])) $event_params['creative_name'] = $custom_data['creative_name'];
                    if (isset($custom_data['items'])) $event_params['items'] = $custom_data['items'];
                    break;
                case 'begin_trial':
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['subscription_plan'])) $event_params['subscription_plan'] = $custom_data['subscription_plan'];
                    if (isset($custom_data['trial_period_days'])) $event_params['trial_period_days'] = (int)$custom_data['trial_period_days'];
                    break;
                case 'subscribe':
                    if (isset($custom_data['transaction_id'])) $event_params['transaction_id'] = $custom_data['transaction_id'];
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    if (isset($custom_data['recurring_interval'])) $event_params['recurring_interval'] = $custom_data['recurring_interval'];
                    break;
                case 'generate_lead':
                    if (isset($custom_data['lead_type'])) $event_params['lead_type'] = $custom_data['lead_type'];
                    if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                    break;
                case 'contact':
                    if (isset($custom_data['method'])) $event_params['method'] = $custom_data['method'];
                    if (isset($custom_data['link_url'])) $event_params['link_url'] = $custom_data['link_url'];
                    break;
                case 'schedule':
                    if (isset($custom_data['appointment_type'])) $event_params['appointment_type'] = $custom_data['appointment_type'];
                    if (isset($custom_data['date_time'])) $event_params['date_time'] = $custom_data['date_time'];
                    break;
                case 'search':
                    if (isset($custom_data['search_term'])) $event_params['search_term'] = $custom_data['search_term'];
                    break;
                case 'select_content':
                    if (isset($custom_data['content_type'])) $event_params['content_type'] = $custom_data['content_type'];
                    if (isset($custom_data['item_id'])) $event_params['item_id'] = $custom_data['item_id'];
                    break;
                case 'share':
                    if (isset($custom_data['method'])) $event_params['method'] = $custom_data['method'];
                    if (isset($custom_data['content_type'])) $event_params['content_type'] = $custom_data['content_type'];
                    if (isset($custom_data['item_id'])) $event_params['item_id'] = $custom_data['item_id'];
                    break;
                case 'file_download':
                    if (isset($custom_data['file_extension'])) $event_params['file_extension'] = $custom_data['file_extension'];
                    if (isset($custom_data['file_name'])) $event_params['file_name'] = $custom_data['file_name'];
                    if (isset($custom_data['link_url'])) $event_params['link_url'] = $custom_data['link_url'];
                    break;
                case 'video_start':
                case 'video_progress':
                case 'video_complete':
                    if (isset($custom_data['video_provider'])) $event_params['video_provider'] = $custom_data['video_provider'];
                    if (isset($custom_data['video_title'])) $event_params['video_title'] = $custom_data['video_title'];
                    if (isset($custom_data['video_url'])) $event_params['video_url'] = $custom_data['video_url'];
                    if (isset($custom_data['percent'])) $event_params['percent'] = (int)$custom_data['percent'];
                    break;
                case 'sign_up':
                case 'login':
                    if (isset($custom_data['method'])) $event_params['method'] = $custom_data['method'];
                    break;
                default:
                    // Merge any remaining parameters as custom params
                    $event_params = array_merge($event_params, $custom_data);
                    break;
            }
        }

        // Format GA4 event payload structure
        $event = [
            'name' => strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $mapped_event_name)),
            'params' => $event_params
        ];

        // Attach Debug Mode if test code/debug mode is set
        $test_code = trim($options['test_code'] ?? '');
        if (!empty($test_code) || is_user_logged_in()) {
            $event['params']['debug_mode'] = true;
        }

        $payload = [
            'client_id' => $client_id,
            'events' => [$event]
        ];
        if (!empty($ga4_user_id)) {
            $payload['user_id'] = $ga4_user_id;
        }

        // Send via Measurement Protocol immediately (non-blocking)
        $base_url = 'https://www.google-analytics.com/mp/collect';
        $is_debug_mode = !empty($test_code) || strpos($test_code, 'debug_mode=true') !== false;
        if ($is_debug_mode) {
            $base_url = 'https://www.google-analytics.com/debug/mp/collect';
        }

        $url = $base_url . '?measurement_id=' . urlencode($measurement_id) . '&api_secret=' . urlencode($api_secret);

        $response = wp_remote_post($url, [
            'method'      => 'POST',
            'timeout'     => 15,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking'    => false,
            'sslverify'   => false,
            'headers'     => ['Content-Type' => 'application/json'],
            'body'        => wp_json_encode($payload),
        ]);

        $status = is_wp_error($response) ? 'failed' : 'dispatched';

        // Log GA4 Event to diagnostics database (status = dispatched)
        global $wpdb;
        $table = $wpdb->prefix . 'pixelonwp_event_logs';
        $wpdb->insert(
            $table,
            [
                'event_name' => sanitize_text_field($event_name),
                'event_id'   => sanitize_text_field($event_id),
                'platform'   => 'ga4',
                'payload'    => wp_json_encode($payload),
                'status'     => $status,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );
    }
}
