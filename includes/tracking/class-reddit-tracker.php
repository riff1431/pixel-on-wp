<?php
/**
 * Reddit Tracker Class.
 *
 * @package PixelOnWP\Includes\Tracking
 */

namespace PixelOnWP\Includes\Tracking;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Reddit_Tracker {

  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader) {
    // DynamicStatus CAPI Event Delivery Hooks for Reddit removed
  }

  private function is_event_enabled($event_name) {
    $active_events = get_option('PixelOnWP_active_events', []);
    $key = strtolower($event_name);

    if ($key === 'completepurchase' || $key === 'purchase') $key = 'purchase';
    if ($key === 'submitform') $key = 'lead';

    if (isset($active_events[$key])) {
      return $active_events[$key] === '1';
    }
    return true; // Default enabled
  }

  public static function dispatch_reddit_server_event_static($event_name, $event_id, $data, $order = null, $user_data_override = null) {
    $capi_map = [
      'page_view' => 'PAGE_VISIT', 'pageview' => 'PAGE_VISIT', 'pagevisit' => 'PAGE_VISIT',
      'view_item' => 'VIEW_CONTENT', 'viewcontent' => 'VIEW_CONTENT',
      'search' => 'SEARCH',
      'add_to_cart' => 'ADD_TO_CART', 'addtocart' => 'ADD_TO_CART',
      'add_to_wishlist' => 'ADD_TO_WISHLIST', 'addtowishlist' => 'ADD_TO_WISHLIST',
      'purchase' => 'PURCHASE', 'completepayment' => 'PURCHASE',
      'lead' => 'LEAD', 'submitform' => 'LEAD',
      'signup' => 'SIGN_UP', 'completeregistration' => 'SIGN_UP', 'complete_registration' => 'SIGN_UP'
    ];
    $event_key = strtolower($event_name);
    $capi_tracking_type = isset($capi_map[$event_key]) ? $capi_map[$event_key] : 'CUSTOM';

    $check_event = strtolower($capi_tracking_type);
    // Map to check option toggle state
    $toggle_map = [
      'page_visit' => 'PageVisit',
      'view_content' => 'ViewContent',
      'search' => 'Search',
      'add_to_cart' => 'AddToCart',
      'add_to_wishlist' => 'AddToWishlist',
      'purchase' => 'Purchase',
      'lead' => 'Lead',
      'sign_up' => 'SignUp',
      'custom' => 'Custom'
    ];
    $check_key = isset($toggle_map[$check_event]) ? $toggle_map[$check_event] : $event_name;

    $active_events = get_option('PixelOnWP_active_events', []);
    if (isset($active_events[strtolower($check_key)]) && $active_events[strtolower($check_key)] === '0') {
      return;
    }

    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!in_array('reddit', $platforms, true)) return;

    $reddit_config = get_option('PixelOnWP_reddit_config', []);
    if (isset($reddit_config['events'][$check_key]) && filter_var($reddit_config['events'][$check_key], FILTER_VALIDATE_BOOLEAN) === false) {
      if (strtolower($event_name) !== 'purchase') {
        return;
      }
    }

    if (strtolower($event_name) === 'purchase') {
      $reddit_purchase_enabled = filter_var(get_option('pixelonwp_reddit_enable_purchase', '0'), FILTER_VALIDATE_BOOLEAN);
      $reddit_purchase_standard_active = !isset($reddit_config['events']['Purchase']) || filter_var($reddit_config['events']['Purchase'], FILTER_VALIDATE_BOOLEAN);
      if (!$reddit_purchase_enabled && !$reddit_purchase_standard_active) {
        return;
      }
    }

    $pixel_id = isset($reddit_config['pixel_id']) ? trim($reddit_config['pixel_id']) : '';
    $access_token = isset($reddit_config['access_token']) ? trim($reddit_config['access_token']) : '';

    if (empty($pixel_id) || empty($access_token)) return;

    $user_data = $user_data_override ? $user_data_override : self::get_hashed_user_data($order);

    if (strtolower($event_name) === 'purchase' && $order) {
      $event_id = 'order_' . $order->get_id();
    }

    $ip_addr = $user_data['client_ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    $user_agent = $user_data['client_user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ip_addr === '127.0.0.1' || $ip_addr === '::1') {
      $ip_addr = '104.28.254.74';
    }

    $reddit_user = [
      'ip_address' => $ip_addr,
      'user_agent' => $user_agent
    ];

    if (!empty($user_data['em'])) $reddit_user['email'] = $user_data['em'];
    if (!empty($user_data['ph'])) $reddit_user['phone_number'] = $user_data['ph'];
    if (!empty($user_data['external_id'])) $reddit_user['external_id'] = $user_data['external_id'];

    // Resolve click_id
    $click_id = '';
    if (isset($_COOKIE['_rdt_cid'])) {
      $click_id = sanitize_text_field(wp_unslash($_COOKIE['_rdt_cid']));
    }
    if (empty($click_id) && isset($_GET['rdt_cid'])) {
      $click_id = sanitize_text_field(wp_unslash($_GET['rdt_cid']));
    }
    if (empty($click_id) && isset($data['click_id'])) {
      $click_id = $data['click_id'];
    }
    if (!empty($click_id)) {
      $reddit_user['click_id'] = $click_id;
    }

    // Resolve uuid
    if (isset($_COOKIE['_rdt_uuid'])) {
      $reddit_user['uuid'] = sanitize_text_field(wp_unslash($_COOKIE['_rdt_uuid']));
    } else {
      $uuid = wp_generate_uuid4();
      $reddit_user['uuid'] = $uuid;
      if (!headers_sent()) {
        setcookie('_rdt_uuid', $uuid, time() + (86400 * 365), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
      }
    }

    $reddit_metadata = [
      'conversion_id' => $event_id
    ];

    if ($order) {
        $reddit_metadata['currency'] = $order->get_currency();
        $reddit_metadata['value'] = (float) $order->get_total();
        $reddit_metadata['item_count'] = (int) $order->get_item_count();
        
        $content_ids = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $pid = $product ? $product->get_id() : $item->get_product_id();
            if ($pid) {
                $content_ids[] = (string)$pid;
            }
        }
        $reddit_metadata['content_ids'] = $content_ids;
    } else {
        if (isset($data['currency'])) $reddit_metadata['currency'] = $data['currency'];
        if (isset($data['value'])) $reddit_metadata['value'] = (float)$data['value'];
        
        $content_ids = [];
        $raw_items = $data['items'] ?? ($data['contents'] ?? []);
        if (!empty($raw_items) && is_array($raw_items)) {
            foreach ($raw_items as $item) {
                $pid = $item['item_id'] ?? ($item['id'] ?? ($item['product_id'] ?? ($item['productId'] ?? ($item['content_id'] ?? ''))));
                if ($pid) {
                    $content_ids[] = (string)$pid;
                }
            }
        }
        if (!empty($content_ids)) {
            $reddit_metadata['content_ids'] = $content_ids;
        }
        if (isset($data['item_count'])) {
            $reddit_metadata['item_count'] = (int)$data['item_count'];
        } elseif (isset($data['quantity'])) {
            $reddit_metadata['item_count'] = (int)$data['quantity'];
        }
    }

    $event_body = [
      'event_type' => [
        'tracking_type' => $capi_tracking_type
      ],
      'event_at' => date('c'),
      'user' => $reddit_user,
      'event_metadata' => $reddit_metadata,
      'action_source' => 'WEBSITE',
      'event_source_url' => home_url($_SERVER['REQUEST_URI'] ?? ''),
      'event_id' => $event_id
    ];

    $payload = [
      'events' => [$event_body]
    ];

    $test_code = isset($reddit_config['test_code']) ? trim($reddit_config['test_code']) : '';
    if (!empty($test_code)) {
      $payload['test_mode'] = true;
    }

    $request_body = wp_json_encode($payload);

    $endpoint = "https://ads-api.reddit.com/api/v2.0/conversions/events/" . $pixel_id;

    $response = wp_remote_post($endpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $access_token,
        'Content-Type' => 'application/json'
      ],
      'body' => $request_body,
      'timeout' => 15,
      'blocking' => false,
      'sslverify' => false
    ]);

    // Log Reddit Event
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';
    $status = is_wp_error($response) ? 'failed' : 'dispatched';
    $wpdb->insert($table, [
      'event_name' => $event_name,
      'event_id' => $event_id,
      'platform' => 'reddit',
      'payload' => $request_body,
      'status' => $status,
      'created_at' => current_time('mysql')
    ]);
  }

  public static function get_hashed_user_data($order = null) {
    $user_data = [
      'client_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
      'client_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];

    $email = ''; $phone = ''; $external_id = '';
    $fname = ''; $lname = ''; $city = ''; $state = ''; $zip = ''; $country = '';

    if ($order) {
      $email = $order->get_billing_email();
      $phone = $order->get_billing_phone();
      $fname = $order->get_billing_first_name();
      $lname = $order->get_billing_last_name();
      $city = $order->get_billing_city();
      $state = $order->get_billing_state();
      $zip = $order->get_billing_postcode();
      $country = $order->get_billing_country();
      $external_id = (string) $order->get_customer_id();
    } else if (is_user_logged_in()) {
      $user = wp_get_current_user();
      $email = $user->user_email;
      $fname = $user->first_name;
      $lname = $user->last_name;
      $external_id = (string) $user->ID;
      $phone = get_user_meta($user->ID, 'billing_phone', true);
      $city = get_user_meta($user->ID, 'billing_city', true);
      $state = get_user_meta($user->ID, 'billing_state', true);
      $zip = get_user_meta($user->ID, 'billing_postcode', true);
      $country = get_user_meta($user->ID, 'billing_country', true);
    }

    if (!empty($email)) $user_data['em'] = hash('sha256', trim(strtolower($email)));
    if (!empty($phone)) {
      $formatted_phone = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::format_phone_e164($phone, $country);
      if (!empty($formatted_phone)) {
        $digits_only_phone = preg_replace('/\D/', '', $formatted_phone);
        $user_data['ph'] = hash('sha256', trim(strtolower($digits_only_phone)));
      } else {
        $digits_only_phone = preg_replace('/\D/', '', $phone);
        $user_data['ph'] = hash('sha256', trim(strtolower($digits_only_phone)));
      }
    }
    if (!empty($fname)) $user_data['fn'] = hash('sha256', trim(strtolower($fname)));
    if (!empty($lname)) $user_data['ln'] = hash('sha256', trim(strtolower($lname)));
    if (!empty($city)) $user_data['ct'] = hash('sha256', trim(strtolower($city)));
    if (!empty($state)) $user_data['st'] = hash('sha256', trim(strtolower($state)));
    if (!empty($zip)) $user_data['zp'] = hash('sha256', trim(strtolower($zip)));
    if (!empty($country)) $user_data['country'] = hash('sha256', trim(strtolower($country)));

    if (!empty($external_id) && $external_id !== '0') $user_data['external_id'] = hash('sha256', trim($external_id));

    return $user_data;
  }
}
