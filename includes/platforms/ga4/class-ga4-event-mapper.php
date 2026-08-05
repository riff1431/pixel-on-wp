<?php
/**
 * GA4 Event Mapper Class.
 *
 * @package PixelOnWP\Includes\Platforms\Ga4
 */

namespace PixelOnWP\Includes\Platforms\Ga4;

if (!defined('ABSPATH')) {
    exit;
}

class PixelOnWP_GA4_Event_Mapper {

    /**
     * Map standard WooCommerce events to GA4 payloads, stripping PII from params.
     *
     * @param string $event_name
     * @param array $custom_data
     * @param array $user_data
     * @param string $event_id
     * @param bool $enhanced_conversions_enabled
     * @return array
     */
    public static function map_event(string $event_name, array $custom_data, array $user_data, string $event_id, bool $enhanced_conversions_enabled = false): array {
        $options = get_option('PixelOnWP_ga4_config', []);
        $measurement_id = trim($options['measurement_id'] ?? get_option('PixelOnWP_ga4_id', ''));
        
        // Resolve client_id
        $client_id = '';
        if (!empty($custom_data['client_id'])) {
            $client_id = sanitize_text_field($custom_data['client_id']);
        } elseif (isset($_COOKIE['_ga'])) {
            $client_id = preg_replace('/^GA\d\.\d\./', '', $_COOKIE['_ga']);
        }
        if (empty($client_id)) {
            $client_id = wp_generate_uuid4();
        }

        // Resolve session_id
        $session_id = '';
        if (!empty($custom_data['session_id'])) {
            $session_id = sanitize_text_field($custom_data['session_id']);
        } else {
            $measurement_id_clean = str_replace('G-', '', $measurement_id);
            $session_cookie_name = '_ga_' . $measurement_id_clean;
            if (isset($_COOKIE[$session_cookie_name])) {
                $parts = explode('.', $_COOKIE[$session_cookie_name]);
                if (count($parts) > 2) {
                    $session_id = sanitize_text_field($parts[2]);
                }
            } elseif (isset($_COOKIE['_ga_sid'])) {
                $session_id = sanitize_text_field($_COOKIE['_ga_sid']);
            }
        }
        if (empty($session_id)) {
            $session_id = strval(time());
        }

        // Base/Default Parameters
        $page_location = sanitize_text_field($custom_data['page_location'] ?? (isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : home_url($_SERVER['REQUEST_URI'] ?? '')));
        $page_title = sanitize_text_field($custom_data['page_title'] ?? get_bloginfo('name'));
        $currency = sanitize_text_field($custom_data['currency'] ?? (function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : 'USD'));

        $event_params = [
            'event_id' => sanitize_text_field($event_id),
            'session_id' => $session_id,
            'page_location' => $page_location,
            'page_title' => $page_title,
            'currency' => $currency,
            'engagement_time_msec' => '100'
        ];

        // Map and clean parameters based on standard events
        switch ($event_name) {
            case 'view_item_list':
            case 'select_item':
                if (isset($custom_data['item_list_id'])) $event_params['item_list_id'] = sanitize_text_field($custom_data['item_list_id']);
                if (isset($custom_data['item_list_name'])) $event_params['item_list_name'] = sanitize_text_field($custom_data['item_list_name']);
                if (isset($custom_data['items'])) $event_params['items'] = self::sanitize_items_array($custom_data['items']);
                break;
            case 'view_item':
            case 'add_to_cart':
            case 'remove_from_cart':
            case 'view_cart':
                if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                if (isset($custom_data['items'])) $event_params['items'] = self::sanitize_items_array($custom_data['items']);
                break;
            case 'begin_checkout':
                if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                if (isset($custom_data['coupon'])) $event_params['coupon'] = sanitize_text_field($custom_data['coupon']);
                if (isset($custom_data['items'])) $event_params['items'] = self::sanitize_items_array($custom_data['items']);
                break;
            case 'add_shipping_info':
                if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                if (isset($custom_data['shipping_tier'])) $event_params['shipping_tier'] = sanitize_text_field($custom_data['shipping_tier']);
                if (isset($custom_data['items'])) $event_params['items'] = self::sanitize_items_array($custom_data['items']);
                break;
            case 'add_payment_info':
                if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                if (isset($custom_data['payment_type'])) $event_params['payment_type'] = sanitize_text_field($custom_data['payment_type']);
                if (isset($custom_data['items'])) $event_params['items'] = self::sanitize_items_array($custom_data['items']);
                break;
            case 'purchase':
                if (isset($custom_data['transaction_id'])) $event_params['transaction_id'] = sanitize_text_field($custom_data['transaction_id']);
                if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                if (isset($custom_data['tax'])) $event_params['tax'] = (float)$custom_data['tax'];
                if (isset($custom_data['shipping'])) $event_params['shipping'] = (float)$custom_data['shipping'];
                if (isset($custom_data['coupon'])) $event_params['coupon'] = sanitize_text_field($custom_data['coupon']);
                if (isset($custom_data['items'])) $event_params['items'] = self::sanitize_items_array($custom_data['items']);
                break;
            case 'refund':
                if (isset($custom_data['transaction_id'])) $event_params['transaction_id'] = sanitize_text_field($custom_data['transaction_id']);
                if (isset($custom_data['value'])) $event_params['value'] = (float)$custom_data['value'];
                if (isset($custom_data['items'])) $event_params['items'] = self::sanitize_items_array($custom_data['items']);
                break;
            default:
                // Non-ecommerce standard parameters copy safely
                foreach ($custom_data as $key => $val) {
                    if (!in_array($key, ['email', 'phone', 'phone_number', 'first_name', 'last_name', 'street', 'city', 'region', 'postal_code', 'country'], true)) {
                        $event_params[sanitize_key($key)] = is_array($val) ? self::sanitize_recursive($val) : sanitize_text_field($val);
                    }
                }
                break;
        }

        // Clean PII hashes from params (strictly strip - PII Integrity Guard)
        unset($event_params['email']);
        unset($event_params['phone']);
        unset($event_params['phone_number']);
        unset($event_params['first_name']);
        unset($event_params['last_name']);
        unset($event_params['em']);
        unset($event_params['ph']);

        $payload = [
            'client_id' => $client_id,
            'events' => [[
                'name' => strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $event_name)),
                'params' => $event_params
            ]]
        ];

        // Format Enhanced Conversions (Root level user_data)
        if ($enhanced_conversions_enabled) {
            $root_user_data = [];
            
            $raw_email = $user_data['email'] ?? '';
            $raw_phone = $user_data['phone_number'] ?? $user_data['phone'] ?? '';

            if (!empty($raw_email)) {
                $clean_email = strtolower(trim($raw_email));
                $root_user_data['sha256_email_address'] = [hash('sha256', $clean_email)];
            }
            if (!empty($raw_phone)) {
                $clean_phone = preg_replace('/[^0-9+]/', '', $raw_phone);
                $root_user_data['sha256_phone_number'] = [hash('sha256', $clean_phone)];
            }

            if (!empty($root_user_data)) {
                $payload['user_data'] = $root_user_data;
            }
        }

        return $payload;
    }

    private static function sanitize_items_array(array $items): array {
        $clean = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $clean_item = [];
                foreach ($item as $k => $v) {
                    $clean_item[sanitize_key($k)] = is_array($v) ? self::sanitize_recursive($v) : sanitize_text_field($v);
                }
                $clean[] = $clean_item;
            }
        }
        return $clean;
    }

    private static function sanitize_recursive(array $arr): array {
        $clean = [];
        foreach ($arr as $k => $v) {
            $clean[sanitize_key($k)] = is_array($v) ? self::sanitize_recursive($v) : sanitize_text_field($v);
        }
        return $clean;
    }
}
