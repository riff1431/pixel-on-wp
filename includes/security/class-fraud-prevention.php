<?php
/**
 * Multi-Layer Fraud Validation Engine with Fall-back Protection.
 *
 * @package PixelOnWP\Includes\Security
 */

namespace PixelOnWP\Includes\Security;

if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Fraud_Prevention
{
  /**
   * Register hooks.
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    // Hook into WooCommerce checkout validation
    $loader->add_action('woocommerce_after_checkout_validation', $this, 'validate_checkout_fraud_risk', 10, 2);
    
    // Enqueue popup script on checkout
    $loader->add_action('wp_enqueue_scripts', $this, 'enqueue_checkout_popup');
    
    // Hook into WooCommerce Admin Order Page to show Fraud Stats
    $loader->add_action('add_meta_boxes', $this, 'add_order_fraud_meta_box');

    // AJAX handlers for fraud logs
    add_action('wp_ajax_PixelOnWP_get_fraud_block_logs', [$this, 'ajax_get_fraud_block_logs']);
    add_action('wp_ajax_PixelOnWP_clear_fraud_block_logs', [$this, 'ajax_clear_fraud_block_logs']);
  }

  /**
   * Add meta box to WooCommerce Order
   */
  public function add_order_fraud_meta_box(): void
  {
    add_meta_box(
      'pixelonwp_fraud_stats_meta_box',
      'Fraud & Delivery History',
      [$this, 'render_order_fraud_meta_box'],
      'shop_order',
      'side',
      'high'
    );
    // HPOS compatibility
    add_meta_box(
      'pixelonwp_fraud_stats_meta_box',
      'Fraud & Delivery History',
      [$this, 'render_order_fraud_meta_box'],
      'woocommerce_page_wc-orders',
      'side',
      'high'
    );
  }

  public function render_order_fraud_meta_box($post_or_order_object): void
  {
    $order = ($post_or_order_object instanceof \WC_Order) ? $post_or_order_object : wc_get_order($post_or_order_object->ID);
    if (!$order) {
        return;
    }

    $phone = preg_replace('/[^0-9]/', '', $order->get_billing_phone());
    if (empty($phone)) {
        echo '<p>No phone number found for this order.</p>';
        return;
    }

    if (strlen($phone) > 11 && substr($phone, 0, 2) === '88') {
      $phone = substr($phone, 2);
    }

    $history = $this->get_courier_history($phone);
    if (!$history) {
        echo '<p>Could not fetch history.</p>';
        return;
    }

    $risk_score = 0;
    if ($history['total_parcels'] > 0) {
       $risk_score = round(($history['returned_parcels'] / $history['total_parcels']) * 100);
    }
    
    $risk_color = $risk_score >= 70 ? '#dc2626' : ($risk_score >= 40 ? '#d97706' : '#16a34a');

    echo '<div style="font-size: 14px; margin-bottom: 10px;">';
    echo '<strong>Phone:</strong> ' . esc_html($phone) . '<br><br>';
    echo '<strong>Total Parcels:</strong> ' . esc_html($history['total_parcels']) . '<br>';
    echo '<strong>Successful:</strong> <span style="color:#16a34a;">' . esc_html($history['successful_deliveries']) . '</span><br>';
    echo '<strong>Returned:</strong> <span style="color:#dc2626;">' . esc_html($history['returned_parcels']) . '</span><br>';
    echo '<hr>';
    echo '<strong>Risk Score:</strong> <span style="font-size: 16px; font-weight: bold; color:' . $risk_color . ';">' . $risk_score . '%</span>';
    echo '</div>';
  }

  /**
   * Get fraud settings with defaults.
   */
  private function get_settings(): array
  {
    return wp_parse_args(get_option('PixelOnWP_fraud_settings', []), [
      'enable_fraud_check'     => '0',
      'risk_threshold'         => 70,
      'warning_message'        => 'Your order cannot be processed due to a high rate of returned parcels on this phone number.',
      'support_phone'          => '',
      'pathao_token'           => '',
      'pathao_client_id'       => '',
      'pathao_client_secret'   => '',
      'pathao_username'        => '',
      'pathao_password'        => '',
      'steadfast_key'          => '',
      'steadfast_secret'       => '',
      'redx_token'             => '',
      // Layer 1 settings
      'enable_layer1'          => '1',
      'phone_length'           => 11,
      'block_dummy_phones'     => '1',
      'block_gibberish_names'  => '1',
      // Layer 2 settings
      'enable_layer2'          => '1',
      // Layer 3 settings
      'enable_layer3'          => '1',
      'velocity_limit'         => 3,
      'velocity_window'        => 24,
      // Layer 4 settings
      'enable_layer4'          => '1',
      // Blocked notice
      'blocked_popup_title'    => 'Order Blocked',
      'blocked_popup_message'  => 'Your order could not be processed. Please contact our support team for assistance.',
      'show_wa_button'         => '1',
      'show_call_button'       => '1',
    ]);
  }

  // =========================================================================
  //  VALIDATION ENGINE - Cascading Fall-back Execution Chain
  // =========================================================================

  /**
   * Main checkout validation entry point.
   */
  public function validate_checkout_fraud_risk($data, $errors): void
  {
    $settings = $this->get_settings();
    if ($settings['enable_fraud_check'] !== '1') {
      return;
    }

    $phone = isset($data['billing_phone']) ? preg_replace('/[^0-9]/', '', $data['billing_phone']) : '';
    $name = isset($data['billing_first_name']) ? sanitize_text_field($data['billing_first_name'] . ' ' . ($data['billing_last_name'] ?? '')) : '';
    $address = isset($data['billing_address_1']) ? sanitize_text_field($data['billing_address_1']) : '';
    $client_ip = $this->get_client_ip();

    // Standardize BD phone number
    if (strlen($phone) > 11 && substr($phone, 0, 2) === '88') {
      $phone = substr($phone, 2);
    }

    // ── LAYER 1: Basic Input & Pattern Validation ──
    if ($settings['enable_layer1'] === '1') {
      $layer1_result = $this->run_layer1($phone, $name, $address, $settings);
      if ($layer1_result !== false) {
        $this->log_block($phone, $client_ip, $layer1_result, 'Layer 1: Input Validation');
        $this->trigger_block($errors, $settings, $layer1_result);
        return;
      }
    }

    // ── LAYER 2: Primary External Courier API Check ──
    $api_succeeded = false;
    if ($settings['enable_layer2'] === '1') {
      $layer2_result = $this->run_layer2($phone, $settings);
      if ($layer2_result === 'blocked') {
        $this->log_block($phone, $client_ip, 'Courier API risk threshold exceeded', 'Layer 2: Courier API');
        $this->trigger_block($errors, $settings, 'Courier API risk threshold exceeded');
        return;
      }
      if ($layer2_result === 'pass') {
        $api_succeeded = true;
      }
      // If $layer2_result === 'api_failed', allow the order to proceed
    }
  }

  /**
   * LAYER 1: Basic Input & Pattern Validation
   */
  private function run_layer1(string $phone, string $name, string $address, array $settings)
  {
    // Phone length check
    $expected_length = (int) ($settings['phone_length'] ?? 11);
    if (!empty($phone) && strlen($phone) !== $expected_length) {
      return 'Phone number must be exactly ' . $expected_length . ' digits.';
    }

    // Dummy phone number check
    if ($settings['block_dummy_phones'] === '1') {
      $dummy_patterns = [
        '/^0{11}$/',                          // 00000000000
        '/^01234567890?$/',                    // 01234567890
        '/^0170{7}$/',                         // 01700000000
        '/^01[0-9](\1{8})$/',                  // all same digits after prefix
        '/^(\d)\1{10}$/',                      // all same digit
      ];
      foreach ($dummy_patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
          return 'Invalid phone number detected (dummy pattern).';
        }
      }
      // Sequential numbers
      if (preg_match('/^0?1234567890?1?$/', $phone) || $phone === '01111111111') {
        return 'Invalid phone number detected (sequential pattern).';
      }
    }

    // Gibberish name/address check
    if ($settings['block_gibberish_names'] === '1') {
      if ($this->is_gibberish($name)) {
        return 'Invalid name detected (gibberish characters).';
      }
      if (!empty($address) && $this->is_gibberish($address)) {
        return 'Invalid address detected (gibberish characters).';
      }
    }

    return false; // Passed
  }

  /**
   * Check if a string looks like gibberish (no vowels in a long run of letters, or just keyboard mash).
   */
  private function is_gibberish(string $text): bool
  {
    $text = strtolower(trim($text));
    if (strlen($text) < 3) {
      return false;
    }

    // Check for consonant-only strings 5+ chars long
    if (preg_match('/^[^aeiou\s\d\W]{5,}$/i', $text)) {
      return true;
    }

    // Known gibberish patterns
    $gibberish = ['asdf', 'qwer', 'zxcv', 'jkl;', 'fdsa', 'rewq', 'vcxz', 'test test', 'aaa', 'bbb', 'xxx', 'zzz'];
    foreach ($gibberish as $g) {
      if (strpos($text, $g) !== false) {
        return true;
      }
    }

    // More than 3 consecutive identical characters
    if (preg_match('/(.)\1{3,}/', $text)) {
      return true;
    }

    return false;
  }

  /**
   * LAYER 2: Primary External Courier API Check
   * Returns: 'blocked', 'pass', or 'api_failed'
   */
  private function run_layer2(string $phone, array $settings): string
  {
    if (empty($phone)) {
      return 'api_failed';
    }

    try {
      $history = $this->get_courier_history($phone);
      
      if (!$history || $history['total_parcels'] === 0) {
        return 'pass'; // No history = new customer, allow
      }

      $risk_score = round(($history['returned_parcels'] / $history['total_parcels']) * 100);
      $threshold = (int) $settings['risk_threshold'];
      
      if ($risk_score >= $threshold) {
        return 'blocked';
      }

      return 'pass';

    } catch (\Exception $e) {
      // API failed, allow the order to proceed
      return 'api_failed';
    }
  }



  /**
   * Log a blocked attempt.
   */
  private function log_block(string $phone, string $ip, string $reason, string $layer): void
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
      // Create table dynamically
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip_address varchar(100) NOT NULL,
        reason text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }

    $wpdb->insert($table, [
      'ip_address'  => $ip . ' / ' . $phone,
      'reason'      => $layer . ': ' . $reason,
      'created_at'  => current_time('mysql'),
    ]);
  }

  /**
   * Trigger block and add WC error.
   */
  private function trigger_block($errors, array $settings, string $reason): void
  {
    $message = esc_html($settings['warning_message']);
    $support_phone = $settings['support_phone'];
    
    $wa_num = '';
    if (!empty($support_phone)) {
      $wa_num = '880' . substr(preg_replace('/[^0-9]/', '', $support_phone), -10);
    }
    
    $popup_title = esc_attr($settings['blocked_popup_title'] ?? 'Order Blocked');
    $show_wa = $settings['show_wa_button'] ?? '1';
    $show_call = $settings['show_call_button'] ?? '1';

    $notice = "<div class='pp-fraud-checkout-block-data' style='display:none;' data-message='{$message}' data-whatsapp='{$wa_num}' data-title='{$popup_title}' data-show-wa='{$show_wa}' data-show-call='{$show_call}'></div>";
    $notice .= "<strong>Checkout Blocked</strong>: " . esc_html($reason);
    
    $errors->add('fraud_risk', $notice);
  }

  /**
   * Get client IP address.
   */
  private function get_client_ip(): string
  {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
      if (!empty($_SERVER[$header])) {
        $ip = explode(',', sanitize_text_field($_SERVER[$header]));
        return trim($ip[0]);
      }
    }
    return '0.0.0.0';
  }

  /**
   * Enqueue checkout popup script.
   */
  public function enqueue_checkout_popup(): void
  {
    if (is_checkout()) {
      wp_enqueue_style('PixelOnWP-fraud-popup', plugin_dir_url(dirname(__FILE__, 2)) . 'assets/css/fraud-popup.css', [], '1.0.0');
      wp_enqueue_script('PixelOnWP-fraud-popup', plugin_dir_url(dirname(__FILE__, 2)) . 'assets/js/frontend-checkout-popup.js', ['jquery'], '1.0.0', true);
    }
  }

  // =========================================================================
  //  COURIER API METHODS (unchanged core logic)
  // =========================================================================

  /**
   * Get consolidated courier history (from cache or API).
   */
  public function get_courier_history(string $phone): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'PixelOnWP_fraud_cache';

    // 1. Check cache (valid for 24 hours)
    $cached = $wpdb->get_row($wpdb->prepare(
      "SELECT courier_data, created_at FROM {$table} WHERE phone_number = %s",
      $phone
    ));

    if ($cached) {
      $age = time() - strtotime($cached->created_at);
      if ($age < 86400) {
        return json_decode($cached->courier_data, true);
      }
    }

    // 2. Cache Miss or Expired: Fetch from APIs
    $settings = $this->get_settings();
    
    $total_parcels = 0;
    $successful = 0;
    $returned = 0;
    $breakdown = [];

    // Pathao
    $pathao = $this->fetch_pathao($phone);
    $total_parcels += $pathao['total'];
    $successful += $pathao['success'];
    $returned += $pathao['returned'];
    $breakdown['pathao'] = $pathao;

    // Steadfast
    $steadfast = $this->fetch_steadfast($phone);
    $total_parcels += $steadfast['total'];
    $successful += $steadfast['success'];
    $returned += $steadfast['returned'];
    $breakdown['steadfast'] = $steadfast;

    // RedX
    $redx = $this->fetch_redx($phone);
    $total_parcels += $redx['total'];
    $successful += $redx['success'];
    $returned += $redx['returned'];
    $breakdown['redx'] = $redx;

    $result = [
      'phone_number' => $phone,
      'total_parcels' => $total_parcels,
      'successful_deliveries' => $successful,
      'returned_parcels' => $returned,
      'breakdown' => $breakdown
    ];

    // 3. Save to cache
    if ($cached) {
      $wpdb->update($table, ['courier_data' => wp_json_encode($result), 'created_at' => current_time('mysql')], ['phone_number' => $phone]);
    } else {
      $wpdb->insert($table, [
        'phone_number' => $phone,
        'courier_data' => wp_json_encode($result),
        'created_at' => current_time('mysql')
      ]);
    }

    return $result;
  }

  private function fetch_pathao($phone) {
    $settings = $this->get_settings();
    $token = $settings['pathao_token'] ?? '';
    
    // Auto-generate token if credentials are provided
    $client_id = $settings['pathao_client_id'] ?? '';
    $client_secret = $settings['pathao_client_secret'] ?? '';
    $username = $settings['pathao_username'] ?? '';
    $password = $settings['pathao_password'] ?? '';

    if (empty($token) && !empty($client_id) && !empty($client_secret) && !empty($username) && !empty($password)) {
      $transient_key = 'pixelonwp_pathao_access_token';
      $cached_token = get_transient($transient_key);
      
      if ($cached_token) {
        $token = $cached_token;
      } else {
        // Issue new token
        $issue_url = "https://courier-api-sandbox.pathao.com/aladdin/api/v1/issue-token";
        $response = wp_remote_post($issue_url, [
          'headers' => ['Content-Type' => 'application/json'],
          'body' => wp_json_encode([
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password
          ]),
          'timeout' => 15
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
          $body = json_decode(wp_remote_retrieve_body($response), true);
          if (isset($body['access_token'])) {
            $token = $body['access_token'];
            $expires_in = (int)($body['expires_in'] ?? 432000); // 5 days
            set_transient($transient_key, $token, min($expires_in - 3600, 4 * DAY_IN_SECONDS));
          }
        }
      }
    }

    if (empty($token)) {
      return ['total' => 0, 'success' => 0, 'returned' => 0];
    }

    $url = "https://courier-api-sandbox.pathao.com/aladdin/api/v1/issue-tracker/customer-info?phone=" . urlencode($phone);
    $response = wp_remote_get($url, [
      'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
      'timeout' => 10
    ]);

    $total = 0; $success = 0; $returned = 0;
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
      $body = json_decode(wp_remote_retrieve_body($response), true);
      if (isset($body['data'])) {
         $total = $body['data']['total_parcels'] ?? 0;
         $success = $body['data']['delivered_parcels'] ?? 0;
         $returned = $body['data']['returned_parcels'] ?? 0;
      }
    }
    return ['total' => (int)$total, 'success' => (int)$success, 'returned' => (int)$returned];
  }

  private function fetch_steadfast($phone) {
    $settings = $this->get_settings();
    $key = $settings['steadfast_key'] ?? '';
    $secret = $settings['steadfast_secret'] ?? '';
    
    if (empty($key) || empty($secret)) {
      return ['total' => 0, 'success' => 0, 'returned' => 0];
    }

    $url = "https://portal.packzy.com/api/v1/fraud_check/" . urlencode($phone);
    $response = wp_remote_get($url, [
      'headers' => ['Api-Key' => $key, 'Secret-Key' => $secret, 'Accept' => 'application/json'],
      'timeout' => 10
    ]);

    $total = 0; $success = 0; $returned = 0;
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
      $body = json_decode(wp_remote_retrieve_body($response), true);
      if ($body && (isset($body['Total_parcels']) || isset($body['total_delivered']))) {
        $total = $body['Total_parcels'] ?? 0;
        $success = $body['total_delivered'] ?? 0;
        $returned = $body['total_cancelled'] ?? 0;
      }
    }
    return ['total' => (int)$total, 'success' => (int)$success, 'returned' => (int)$returned];
  }

  private function fetch_redx($phone) {
    $settings = $this->get_settings();
    $token = $settings['redx_token'] ?? '';
    
    if (empty($token)) {
      return ['total' => 0, 'success' => 0, 'returned' => 0];
    }

    $url = "https://sandbox.redx.com.bd/v1.0.0-beta/customer-profile?phone=" . urlencode($phone);
    $response = wp_remote_get($url, [
      'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
      'timeout' => 10
    ]);

    $total = 0; $success = 0; $returned = 0;
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
      $body = json_decode(wp_remote_retrieve_body($response), true);
      if (isset($body['data'])) {
        $total = $body['data']['total'] ?? 0;
        $success = $body['data']['delivered'] ?? 0;
        $returned = $body['data']['returned'] ?? 0;
      }
    }
    return ['total' => (int)$total, 'success' => (int)$success, 'returned' => (int)$returned];
  }

  // =========================================================================
  //  AJAX HANDLERS FOR FRAUD BLOCK LOGS (Tab 4)
  // =========================================================================

  public function ajax_get_fraud_block_logs(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip_address varchar(100) NOT NULL,
        reason text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }

    $results = $wpdb->get_results("SELECT id, ip_address, reason, created_at FROM {$table} ORDER BY created_at DESC LIMIT 100");
    
    $logs = [];
    foreach ($results as $row) {
      $logs[] = [
        'id'         => $row->id,
        'ip'         => $row->ip_address,
        'reason'     => $row->reason,
        'time'       => $row->created_at,
        'time_ago'   => human_time_diff(strtotime($row->created_at), current_time('timestamp')) . ' ago',
      ];
    }

    wp_send_json_success(['logs' => $logs]);
  }

  public function ajax_clear_fraud_block_logs(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized.']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip_address varchar(100) NOT NULL,
        reason text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
      ) $charset_collate;";
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }
    
    $wpdb->query("TRUNCATE TABLE {$table}");

    wp_send_json_success(['message' => 'Fraud block logs cleared.']);
  }
}
