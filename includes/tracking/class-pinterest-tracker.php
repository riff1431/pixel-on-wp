<?php
/**
 * Pinterest Tracker Class.
 *
 * @package PixelOnWP\Includes\Tracking
 */

namespace PixelOnWP\Includes\Tracking;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Pinterest_Tracker {

  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader) {
    // Hooks can be added here if needed
  }

  public static function dispatch_pinterest_server_event_static($event_name, $event_id, $data, $order = null, $user_data_override = null) {
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms) || !in_array('pinterest', $platforms, true)) {
      return;
    }

    $pin_config = get_option('PixelOnWP_pinterest_config', []);
    $tag_id = isset($pin_config['tag_id']) ? trim($pin_config['tag_id']) : '';
    $access_token = isset($pin_config['access_token']) ? trim($pin_config['access_token']) : '';
    $ad_account_id = isset($pin_config['ad_account_id']) ? trim($pin_config['ad_account_id']) : '';

    // If ad account ID is empty, fallback to the tag ID
    if (empty($ad_account_id)) {
      $ad_account_id = $tag_id;
    }

    if (empty($tag_id) || empty($access_token) || empty($ad_account_id)) {
      return;
    }

    // Map the incoming event name to action keys
    $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $event_name));
    $clean_to_action_key = [
      'pageview' => 'PageView', 'pagevisit' => 'PageView',
      'viewitem' => 'ViewContent', 'viewcontent' => 'ViewContent', 'viewitemlist' => 'ViewContent',
      'search' => 'Search',
      'addtocart' => 'AddToCart',
      'begincheckout' => 'InitiateCheckout', 'initiatecheckout' => 'InitiateCheckout', 'startcheckout' => 'InitiateCheckout',
      'purchase' => 'Purchase', 'completepurchase' => 'Purchase', 'completepayment' => 'Purchase',
      'lead' => 'Lead', 'submitform' => 'Lead',
      'signup' => 'CompleteRegistration', 'completeregistration' => 'CompleteRegistration',
      'download' => 'Download',
      'contact' => 'Contact',
      'schedule' => 'Schedule'
    ];
    $action_key = isset($clean_to_action_key[$normalized]) ? $clean_to_action_key[$normalized] : '';

    // Check if event is enabled in configuration
    if ($action_key && isset($pin_config['events'])) {
        $is_enabled = $pin_config['events'][$action_key] ?? null;
        if ($is_enabled === false || $is_enabled === '0' || $is_enabled === 'false') {
            return;
        }
    }

    // Determine Pinterest event category from dropdown mappings (browser format)
    $pin_event_browser = 'pagevisit';
    if ($action_key && !empty($pin_config['mappings'][$action_key])) {
        $pin_event_browser = $pin_config['mappings'][$action_key];
    } else {
        $pin_map = [
          'PageView' => 'pagevisit',
          'ViewContent' => 'pagevisit',
          'Search' => 'search',
          'AddToCart' => 'addtocart',
          'InitiateCheckout' => 'initiatecheckout',
          'Purchase' => 'checkout',
          'Lead' => 'lead',
          'CompleteRegistration' => 'signup',
          'Download' => 'lead',
          'Contact' => 'lead',
          'Schedule' => 'lead'
        ];
        if ($action_key && isset($pin_map[$action_key])) {
            $pin_event_browser = $pin_map[$action_key];
        }
    }

    // Convert browser format to CAPI snake_case format (Pinterest API v5 requirement)
    $browser_to_capi = [
        'pagevisit' => 'page_visit',
        'addtocart' => 'add_to_cart',
        'checkout' => 'checkout',
        'search' => 'search',
        'lead' => 'lead',
        'signup' => 'signup',
        'watchvideo' => 'watch_video',
        'viewcategory' => 'view_category',
        'initiatecheckout' => 'checkout',
        'custom' => 'custom'
    ];
    $pin_event_name = isset($browser_to_capi[$pin_event_browser]) ? $browser_to_capi[$pin_event_browser] : $pin_event_browser;

    // Resolve user data
    $raw_user_info = $user_data_override ? $user_data_override : self::get_user_info_data($order);
    $user_data = [];

    // Add Client IP & User Agent (required by CAPI)
    $ip_address = $raw_user_info['client_ip_address'] ?? ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip_address === '127.0.0.1' || $ip_address === '::1') {
      $ip_address = '104.28.254.74'; // Standard proxy fallback
    }
    $user_data['client_ip_address'] = $ip_address;
    $user_data['client_user_agent'] = $raw_user_info['client_user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');

    // First Party Click IDs
    $click_id = '';
    if (isset($_COOKIE['_pin_unassigned'])) {
      $click_id = sanitize_text_field(wp_unslash($_COOKIE['_pin_unassigned']));
    }
    if (empty($click_id) && isset($_COOKIE['_ppa'])) {
      $click_id = sanitize_text_field(wp_unslash($_COOKIE['_ppa']));
    }
    if (!empty($click_id)) {
      $user_data['click_id'] = $click_id;
    }

    // Hash Enhanced Match Data if enabled
    // Map Enhanced Match Data
    $enable_em = !isset($pin_config['enhanced_match']) || filter_var($pin_config['enhanced_match'], FILTER_VALIDATE_BOOLEAN);
    if ($enable_em) {
      $fields_to_hash = [
        'em' => 'email',
        'ph' => 'phone',
        'fn' => 'first_name',
        'ln' => 'last_name',
        'ct' => 'city',
        'st' => 'state',
        'zp' => 'zip',
        'country' => 'country'
      ];
      
      foreach ($fields_to_hash as $pin_key => $raw_key) {
        // If the override already provided the hashed value (e.g. 'em' from Meta tracker)
        if (!empty($raw_user_info[$pin_key])) {
          // Meta tracker returns hashed strings for em, ph, etc. Pinterest requires an array of strings.
          $val = is_array($raw_user_info[$pin_key]) ? $raw_user_info[$pin_key][0] : $raw_user_info[$pin_key];
          $user_data[$pin_key] = [ (string) $val ];
        } 
        // Else if raw unhashed data is provided
        else if (!empty($raw_user_info[$raw_key])) {
          $user_data[$pin_key] = [ hash('sha256', trim(strtolower($raw_user_info[$raw_key]))) ];
        }
      }
    }

    // Build Custom Data
    $custom_data = [];
    $value = null;
    $currency = null;
    $item_count = 0;
    $line_items = [];

    if ($order) {
      $value = $order->get_total();
      $currency = $order->get_currency();
      foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product) {
          $line_items[] = [
            'product_name' => $product->get_name(),
            'product_id' => (string) $product->get_id(),
            'product_price' => (float) $product->get_price(),
            'product_quantity' => (int) $item->get_quantity()
          ];
          $item_count += (int) $item->get_quantity();
        }
      }
    } else {
      if (isset($data['value'])) $value = $data['value'];
      if (isset($data['currency'])) $currency = $data['currency'];
    }

    if ($value !== null) {
      $custom_data['value'] = (float) number_format((float)$value, 2, '.', '');
    }
    if ($currency !== null) {
      $custom_data['currency'] = sanitize_text_field($currency);
    }
    if (!empty($line_items)) {
      $custom_data['line_items'] = $line_items;
      $custom_data['num_items'] = $item_count;
    }

    // Setup source URL
    $source_url = home_url($_SERVER['REQUEST_URI'] ?? '');

    // Format single event
    $event_object = [
      'event_name' => $pin_event_name,
      'action_source' => 'web',
      'event_time' => time(),
      'event_id' => $event_id,
      'event_source_url' => esc_url_raw($source_url),
      'user_data' => $user_data
    ];

    if (!empty($custom_data)) {
      $event_object['custom_data'] = $custom_data;
    }

    $payload = [
      'data' => [ $event_object ]
    ];

    // Build dispatch URL
    $endpoint = "https://api.pinterest.com/v5/ad_accounts/{$ad_account_id}/events";
    if (!empty($pin_config['test_mode'])) {
      $endpoint .= '?test=true';
    }

    $request_body = wp_json_encode($payload);

    // Dispatch POST Request
    $response = wp_remote_post($endpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $access_token,
        'Content-Type' => 'application/json'
      ],
      'body' => $request_body,
      'timeout' => 15,
      'blocking' => true,
      'sslverify' => false
    ]);

    // Handle Status and HTTP Codes
    $status = 'failed';
    if (!is_wp_error($response)) {
      $code = wp_remote_retrieve_response_code($response);
      if ($code >= 200 && $code < 300) {
        $status = 'success';
      } else {
        $status = 'failed_code_' . $code;
      }
    }

    // Log event in database table
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';
    $wpdb->insert($table, [
      'event_name' => $pin_event_name,
      'event_id' => $event_id,
      'platform' => 'pinterest',
      'payload' => $request_body,
      'status' => $status,
      'created_at' => current_time('mysql')
    ]);
  }

  public static function get_user_info_data($order = null) {
    $user_data = [];
    $email = '';
    $phone = '';
    $fname = '';
    $lname = '';
    $city = '';
    $state = '';
    $zip = '';
    $country = '';

    if ($order) {
      $email = $order->get_billing_email();
      $phone = $order->get_billing_phone();
      $fname = $order->get_billing_first_name();
      $lname = $order->get_billing_last_name();
      $city = $order->get_billing_city();
      $state = $order->get_billing_state();
      $zip = $order->get_billing_postcode();
      $country = $order->get_billing_country();
    } else if (is_user_logged_in()) {
      $user = wp_get_current_user();
      $email = $user->user_email;
      $fname = $user->first_name;
      $lname = $user->last_name;
      $uid = $user->ID;
      $phone = get_user_meta($uid, 'billing_phone', true);
      $city = get_user_meta($uid, 'billing_city', true);
      $state = get_user_meta($uid, 'billing_state', true);
      $zip = get_user_meta($uid, 'billing_postcode', true);
      $country = get_user_meta($uid, 'billing_country', true);
    }

    $user_data['email'] = $email;
    $user_data['phone'] = $phone;
    $user_data['first_name'] = $fname;
    $user_data['last_name'] = $lname;
    $user_data['city'] = $city;
    $user_data['state'] = $state;
    $user_data['zip'] = $zip;
    $user_data['country'] = $country;
    $user_data['client_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $user_data['client_ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';

    return $user_data;
  }
}
