<?php
/**
 * Admin AJAX Handler Class.
 *
 * @package PixelOnWP\Includes\Admin
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Admin_Ajax
{
  /**
   * Register hooks.
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    $loader->add_action('wp_ajax_PixelOnWP_save_platforms', $this, 'save_platforms');
    $loader->add_action('wp_ajax_PixelOnWP_save_wizard', $this, 'save_wizard');
    $loader->add_action('wp_ajax_PixelOnWP_save_gtm', $this, 'save_gtm');
    $loader->add_action('wp_ajax_PixelOnWP_save_ga4', $this, 'save_ga4');
    $loader->add_action('wp_ajax_PixelOnWP_save_ga4_custom_events', $this, 'save_ga4_custom_events');
    $loader->add_action('wp_ajax_PixelOnWP_save_events_builder', $this, 'save_events_builder');
    $loader->add_action('wp_ajax_PixelOnWP_clear_website_cache', $this, 'clear_website_cache');
    $loader->add_action('wp_ajax_PixelOnWP_save_server_config', $this, 'save_server_config');
    $loader->add_action('wp_ajax_PixelOnWP_get_logs', $this, 'get_logs');
    $loader->add_action('wp_ajax_PixelOnWP_clear_logs', $this, 'clear_logs_table');
    $loader->add_action('wp_ajax_PixelOnWP_get_dashboard_stats', $this, 'get_dashboard_stats');
    $loader->add_action('wp_ajax_PixelOnWP_retry_queue', $this, 'retry_queue');
    $loader->add_action('wp_ajax_PixelOnWP_clear_all_data', $this, 'clear_all_data');
    $loader->add_action('wp_ajax_PixelOnWP_get_events_manager_data', $this, 'get_events_manager_data');
    $loader->add_action('wp_ajax_PixelOnWP_save_fraud_settings', $this, 'save_fraud_settings');
    $loader->add_action('wp_ajax_PixelOnWP_courier_lookup', $this, 'courier_lookup');
    $loader->add_action('wp_ajax_PixelOnWP_get_recent_fraud_checks', $this, 'get_recent_fraud_checks');
    $loader->add_action('wp_ajax_PixelOnWP_toggle_event_state', $this, 'toggle_event_state');
    $loader->add_action('wp_ajax_PixelOnWP_toggle_event_param_state', $this, 'toggle_event_param_state');
    $loader->add_action('wp_ajax_PixelOnWP_save_platform_config', $this, 'save_platform_config');
    $loader->add_action('wp_ajax_PixelOnWP_remove_platform_config', $this, 'remove_platform_config');
    $loader->add_action('wp_ajax_pixelonwp_save_header_footer', $this, 'save_header_footer');
    $loader->add_action('wp_ajax_pixelonwp_save_cookie_consent', $this, 'save_cookie_consent');
    $loader->add_action('wp_ajax_pixelonwp_export_consent_logs', $this, 'export_consent_logs');
    $loader->add_action('wp_ajax_PixelOnWP_generate_privacy_policy', $this, 'generate_privacy_policy');
    
    // Universal Tracker hooks
    $loader->add_action('wp_ajax_PixelOnWP_save_tracker_rule', $this, 'save_tracker_rule');
    $loader->add_action('wp_ajax_PixelOnWP_delete_tracker_rule', $this, 'delete_tracker_rule');
    $loader->add_action('wp_ajax_PixelOnWP_toggle_tracker_rule', $this, 'toggle_tracker_rule');
    $loader->add_action('wp_ajax_PixelOnWP_save_tracker_platforms', $this, 'save_tracker_platforms');
    $loader->add_action('wp_ajax_PixelOnWP_toggle_live_debugger', $this, 'toggle_live_debugger');
    
    // Public logging hooks
    $loader->add_action('wp_ajax_nopriv_pixelonwp_log_consent_proof', $this, 'log_consent_proof');
    $loader->add_action('wp_ajax_pixelonwp_log_consent_proof', $this, 'log_consent_proof');
    
    // Allow frontend JS to log events to Diagnostics
    $loader->add_action('wp_ajax_PixelOnWP_log_frontend_event', $this, 'log_frontend_event');
    $loader->add_action('wp_ajax_nopriv_PixelOnWP_log_frontend_event', $this, 'log_frontend_event');
  }

  /**
   * Generates a dynamic privacy policy text based on active features.
   */
  public function generate_privacy_policy(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized.']);
    }

    $policy = "<h3>Privacy Policy & Data Processing Information</h3>\n\n";
    $policy .= "<p>We take your privacy seriously. This policy outlines how we process your data to provide a secure and seamless shopping experience.</p>\n\n";
    
    $features = [];
    
    // Check Fraud Settings
    $fraud = get_option('PixelOnWP_fraud_settings', []);
    if (($fraud['enable_fraud_check'] ?? '0') === '1') {
      $features[] = "<strong>Order Security & Fraud Prevention:</strong> To prevent fraudulent orders, your IP address, billing name, address, and phone number are validated during checkout. This data may be securely checked against courier API databases (e.g., Steadfast, Pathao, RedX) to calculate delivery success ratios. This ensures secure processing and reduces return risks.";
    }

    // Check Meta / Google Tracking
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!empty($platforms)) {
      $features[] = "<strong>Analytics & Marketing Sync:</strong> To improve our services and offer relevant products, we may track browsing behavior (e.g., page views, add to cart actions). Limited hashed order data may be synchronized with platforms like Meta (Facebook) or Google via secure server-side tracking (CAPI) solely for analytics and attribution.";
    }

    // Check WhatsApp
    $ecommerce = get_option('pixelonwp_ecommerce_settings', []);
    if (($ecommerce['wa_messaging_enabled'] ?? '0') === '1') {
      $features[] = "<strong>Order Communications (WhatsApp):</strong> We process your billing phone number to send transactional updates and order confirmations directly via WhatsApp. This ensures you receive timely delivery notifications.";
    }

    // Default Fallback
    if (empty($features)) {
      $policy .= "<p>We process basic order information to fulfill your requests. Your data is kept secure and confidential.</p>";
    } else {
      $policy .= "<ul>";
      foreach ($features as $f) {
        $policy .= "<li>{$f}</li>";
      }
      $policy .= "</ul>";
    }

    $policy .= "\n<p><strong>Strict Privacy Commitment:</strong> We never sell, rent, or trade your personally identifiable information (PII) to third parties for marketing purposes. Your data is solely used for order fulfillment, fraud protection, and essential platform analytics.</p>";

    wp_send_json_success(['policy' => $policy]);
  }

  /**
   * @return void
   */
  public function save_header_footer(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $hf_header = isset($_POST['hf_header']) ? wp_unslash($_POST['hf_header']) : '';
    $hf_body = isset($_POST['hf_body']) ? wp_unslash($_POST['hf_body']) : '';
    $hf_footer = isset($_POST['hf_footer']) ? wp_unslash($_POST['hf_footer']) : '';

    update_option('PixelOnWP_hf_header', $hf_header);
    update_option('PixelOnWP_hf_body', $hf_body);
    update_option('PixelOnWP_hf_footer', $hf_footer);

    wp_send_json_success(['message' => 'Header and Footer configuration saved.']);
  }

  /**
   * @return void
   */
  public function save_cookie_consent(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $payload = isset($_POST['payload']) ? json_decode(wp_unslash($_POST['payload']), true) : [];
    if (!is_array($payload)) {
      wp_send_json_error(['message' => 'Invalid data.'], 400);
    }

    // Since this can contain raw HTML/JS for scripts and custom CSS, we must selectively sanitize.
    $sanitized = $this->sanitize_cookie_consent_payload($payload);
    
    update_option('PixelOnWP_cookie_consent', $sanitized);

    wp_send_json_success(['message' => 'Cookie Consent settings saved.']);
  }

  private function sanitize_cookie_consent_payload(array $payload): array
  {
    // Basic fields
    $sanitized = [
      'enabled' => !empty($payload['enabled']),
      'mode' => sanitize_text_field($payload['mode'] ?? 'strict'),
      'cm_v2' => !empty($payload['cm_v2']),
      'fallback_behavior' => sanitize_text_field($payload['fallback_behavior'] ?? 'strict'),
      'geo_engine' => sanitize_text_field($payload['geo_engine'] ?? 'cloudflare'),
      'geo_rules' => [],
      'scripts' => [],
      'banner' => [],
      'ecommerce' => ['whitelist_cart' => !empty($payload['ecommerce']['whitelist_cart'])],
      'logs' => ['enabled' => !empty($payload['logs']['enabled'])]
    ];

    // Geo Rules
    if (!empty($payload['geo_rules']) && is_array($payload['geo_rules'])) {
      foreach ($payload['geo_rules'] as $rule) {
        $sanitized['geo_rules'][] = [
          'region_name' => sanitize_text_field($rule['region_name'] ?? ''),
          'countries' => array_map('sanitize_text_field', $rule['countries'] ?? []),
          'banner_behavior' => sanitize_text_field($rule['banner_behavior'] ?? 'opt_in'),
          'reject_btn' => sanitize_text_field($rule['reject_btn'] ?? 'yes'),
        ];
      }
    }

    // Scripts (Keep content relatively raw as it contains JS/HTML)
    if (!empty($payload['scripts']) && is_array($payload['scripts'])) {
      foreach ($payload['scripts'] as $script) {
        $sanitized['scripts'][] = [
          'name' => sanitize_text_field($script['name'] ?? ''),
          'category' => sanitize_text_field($script['category'] ?? 'marketing'),
          'location' => sanitize_text_field($script['location'] ?? 'head'),
          'content' => wp_kses_post($script['content'] ?? '') // Allows script tags if user has unfiltered_html, else escapes. Assuming manage_options allows it.
        ];
      }
    }

    // Banner
    if (!empty($payload['banner']) && is_array($payload['banner'])) {
      $b = $payload['banner'];
      $sanitized['banner'] = [
        'layout' => sanitize_text_field($b['layout'] ?? 'floating_bottom'),
        'policy_url' => esc_url_raw($b['policy_url'] ?? ''),
        'title' => sanitize_text_field($b['title'] ?? ''),
        'description' => sanitize_textarea_field($b['description'] ?? ''),
        'btn_accept' => sanitize_text_field($b['btn_accept'] ?? 'Accept All'),
        'btn_reject' => sanitize_text_field($b['btn_reject'] ?? 'Reject All'),
        'btn_prefs' => sanitize_text_field($b['btn_prefs'] ?? 'Cookie Settings'),
        'btn_save' => sanitize_text_field($b['btn_save'] ?? 'Save My Preferences'),
        'cat_necessary_title' => sanitize_text_field($b['cat_necessary_title'] ?? 'Strictly Necessary'),
        'cat_necessary_desc' => sanitize_textarea_field($b['cat_necessary_desc'] ?? ''),
        'cat_analytics_title' => sanitize_text_field($b['cat_analytics_title'] ?? 'Analytics & Performance'),
        'cat_analytics_desc' => sanitize_textarea_field($b['cat_analytics_desc'] ?? ''),
        'cat_marketing_title' => sanitize_text_field($b['cat_marketing_title'] ?? 'Marketing & Targeting'),
        'cat_marketing_desc' => sanitize_textarea_field($b['cat_marketing_desc'] ?? ''),
        'cat_functional_title' => sanitize_text_field($b['cat_functional_title'] ?? 'Functional & Preferences'),
        'cat_functional_desc' => sanitize_textarea_field($b['cat_functional_desc'] ?? ''),
        'color_bg' => sanitize_hex_color($b['color_bg'] ?? '#1e293b'),
        'color_text' => sanitize_hex_color($b['color_text'] ?? '#f8fafc'),
        'color_btn' => sanitize_hex_color($b['color_btn'] ?? '#3b82f6'),
        'custom_css' => wp_strip_all_tags($b['custom_css'] ?? ''), // basic strip for CSS
        'expiry_days' => intval($b['expiry_days'] ?? 365)
      ];
    }

    return $sanitized;
  }

  /**
   * Export Consent Logs to CSV.
   *
   * @since 1.0.0
   * @return void
   */
  public function export_consent_logs(): void
  {
    if (!current_user_can('manage_options') || empty($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'PixelOnWP_nonce')) {
      wp_die('Unauthorized access.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pixelonwp_consent_logs';
    $results = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id DESC LIMIT 5000", ARRAY_A);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=consent-logs-' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Timestamp', 'IP Hash', 'Country', 'Policy Version', 'Status']);

    if (!empty($results)) {
      foreach ($results as $row) {
        fputcsv($output, $row);
      }
    }
    fclose($output);
    exit;
  }

  /**
   * Log Consent Proof from frontend.
   *
   * @since 1.0.0
   * @return void
   */
  public function log_consent_proof(): void
  {
    // Validate request
    if (!isset($_POST['status'])) {
      wp_send_json_error(['message' => 'Missing data.']);
    }

    $config = get_option('PixelOnWP_cookie_consent', []);
    if (empty($config['logs']['enabled']) || $config['logs']['enabled'] === 'false') {
      wp_send_json_success(['message' => 'Logging disabled.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'pixelonwp_consent_logs';
    
    // Avoid fatal error if table isn't created yet
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
       wp_send_json_error(['message' => 'Table not found.']);
    }

    $status = sanitize_text_field($_POST['status']);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip_hash = hash('sha256', $ip . wp_salt()); // Anonymize IP
    $country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']) : 'UNKNOWN';
    $policy = 'v1.0';

    $wpdb->insert(
      $table_name,
      [
        'ip_hash' => $ip_hash,
        'country' => $country,
        'policy_version' => $policy,
        'status' => $status
      ],
      ['%s', '%s', '%s', '%s']
    );

    wp_send_json_success(['message' => 'Consent logged.']);
  }

  /**
   * Handle saving the server configuration.
   *
   * @return void
   */
  public function save_server_config(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $facebook_tracking_mode = isset($_POST['facebook_tracking_mode']) ? sanitize_text_field($_POST['facebook_tracking_mode']) : 'hybrid';
    $tiktok_tracking_mode = isset($_POST['tiktok_tracking_mode']) ? sanitize_text_field($_POST['tiktok_tracking_mode']) : 'hybrid';
    $reddit_tracking_mode = isset($_POST['reddit_tracking_mode']) ? sanitize_text_field($_POST['reddit_tracking_mode']) : 'hybrid';
    $custom_route = isset($_POST['custom_route']) ? sanitize_text_field($_POST['custom_route']) : 'wp-json/pixelonwp/v1/collect';

    update_option('PixelOnWP_facebook_tracking_mode', $facebook_tracking_mode);
    update_option('PixelOnWP_tiktok_tracking_mode', $tiktok_tracking_mode);
    update_option('PixelOnWP_reddit_tracking_mode', $reddit_tracking_mode);
    update_option('PixelOnWP_custom_route', $custom_route);

    // Automatically flush cache so changes reflect instantly
    $this->flush_all_caches();

    wp_send_json_success([
      'message' => 'Server configuration saved successfully.'
    ]);
  }

  /**
   * Handle saving the complete wizard configuration.
   *
   * @return void
   */
  public function save_wizard(): void
  {
    // Security check
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $platforms = isset($_POST['platforms']) ? json_decode(wp_unslash($_POST['platforms']), true) : [];
    $meta = isset($_POST['meta']) ? json_decode(wp_unslash($_POST['meta']), true) : [];
    $events = isset($_POST['events']) ? json_decode(wp_unslash($_POST['events']), true) : [];

    // Sanitize and save data
    $sanitized_platforms = is_array($platforms) ? array_map('sanitize_text_field', $platforms) : [];
    
    $meta_pixels_input = [];
    if (isset($meta['pixels']) && is_array($meta['pixels'])) {
      $meta_pixels_input = $meta['pixels'];
    } elseif (!empty($meta['pixelId']) || !empty($meta['pixel_id'])) {
      $meta_pixels_input = [$meta];
    }

    $sanitized_pixels = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Multi_Pixel_Helper::sanitize_pixels($meta_pixels_input);

    $sanitized_meta = [
      'pixels'     => $sanitized_pixels,
      'pixel_id'   => $sanitized_pixels[0]['pixel_id'] ?? '',
      'capi_token' => $sanitized_pixels[0]['capi_token'] ?? '',
      'test_code'  => $sanitized_pixels[0]['test_code'] ?? '',
    ];
    
    $tiktok = isset($_POST['tiktok']) ? json_decode(wp_unslash($_POST['tiktok']), true) : [];
    $sanitized_tiktok = [
      'pixel_id' => sanitize_text_field($tiktok['pixelId'] ?? ''),
      'access_token' => sanitize_textarea_field($tiktok['accessToken'] ?? ''),
      'test_code' => sanitize_text_field($tiktok['testCode'] ?? ''),
    ];

    $reddit = isset($_POST['reddit']) ? json_decode(wp_unslash($_POST['reddit']), true) : [];
    $sanitized_reddit = [
      'pixel_id' => sanitize_text_field($reddit['pixelId'] ?? ''),
      'access_token' => sanitize_textarea_field($reddit['accessToken'] ?? ''),
      'test_code' => sanitize_text_field($reddit['testCode'] ?? ''),
    ];
    
    $sanitized_events = [];
    if (is_array($events)) {
      foreach ($events as $evt => $enabled) {
        $sanitized_events[sanitize_key($evt)] = $enabled ? '1' : '0';
      }
    }

    // Update DB
    update_option('PixelOnWP_selected_platforms', $sanitized_platforms);
    update_option('PixelOnWP_meta_config', $sanitized_meta);
    update_option('PixelOnWP_tiktok_config', $sanitized_tiktok);
    update_option('PixelOnWP_reddit_config', $sanitized_reddit);
    update_option('PixelOnWP_active_events', $sanitized_events);

    // Flush cache so changes reflect instantly
    $this->flush_all_caches();

    wp_send_json_success([
      'message' => 'Setup wizard configuration saved successfully.'
    ]);
  }

  /**
   * Handle saving the selected platforms from Setup Wizard Step 1.
   *
   * @return void
   */
  public function save_platforms(): void
  {
    // Security check
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $platforms = isset($_POST['platforms']) ? $_POST['platforms'] : [];
    
    // Sanitize incoming array
    $sanitized_platforms = [];
    if (is_array($platforms)) {
      foreach ($platforms as $platform) {
        $sanitized_platforms[] = sanitize_text_field(wp_unslash($platform));
      }
    }

    // Save to database
    $updated = update_option('PixelOnWP_selected_platforms', $sanitized_platforms);

    wp_send_json_success([
      'message' => 'Platforms saved successfully.',
      'platforms' => $sanitized_platforms
    ]);
  }

  /**
   * Handle saving an individual platform configuration progressively.
   *
   * @return void
   */
  public function save_platform_config(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $platform = isset($_POST['platform']) ? sanitize_text_field(wp_unslash($_POST['platform'])) : '';
    $data = isset($_POST['data']) ? json_decode(wp_unslash($_POST['data']), true) : [];
    
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (!is_array($platforms)) $platforms = [];
    
    if ($platform === 'facebook') {
      $meta_pixels_input = [];
      if (isset($data['pixels']) && is_array($data['pixels'])) {
        $meta_pixels_input = $data['pixels'];
      } elseif (!empty($data['pixelId']) || !empty($data['pixel_id'])) {
        $meta_pixels_input = [$data];
      }

      $sanitized_pixels = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Multi_Pixel_Helper::sanitize_pixels($meta_pixels_input);

      $events_sanitized = [];
      if (isset($data['events']) && is_array($data['events'])) {
        foreach ($data['events'] as $evt => $val) {
          $events_sanitized[sanitize_text_field($evt)] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }
      }

      $sanitized_meta = [
        'pixels'     => $sanitized_pixels,
        'pixel_id'   => $sanitized_pixels[0]['pixel_id'] ?? '',
        'capi_token' => $sanitized_pixels[0]['capi_token'] ?? '',
        'test_code'  => $sanitized_pixels[0]['test_code'] ?? '',
        'events'     => $events_sanitized,
      ];
      update_option('PixelOnWP_meta_config', $sanitized_meta);
      
      if (!in_array('facebook', $platforms)) {
          $platforms[] = 'facebook';
      }
      
      // Automatically generate Facebook Product Feed CSV on connecting pixel
      if (class_exists('\\PixelOnWP\\Ecommerce\\PixelOnWP_Product_Feed_Generator')) {
          $feed_generator = new \PixelOnWP\Ecommerce\PixelOnWP_Product_Feed_Generator();
          $feed_generator->generate_feed_file('meta', 'facebook-catalog-feed', 'all');
      }
    } elseif ($platform === 'tiktok') {
      $sanitized_tiktok = [
        'pixel_id' => sanitize_text_field($data['pixelId'] ?? ''),
        'access_token' => sanitize_textarea_field($data['accessToken'] ?? ''),
        'test_code' => sanitize_text_field($data['testCode'] ?? ''),
        'events' => [],
      ];
      if (isset($data['events']) && is_array($data['events'])) {
          foreach ($data['events'] as $evt => $val) {
              $sanitized_tiktok['events'][sanitize_text_field($evt)] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
          }
      }
      update_option('PixelOnWP_tiktok_config', $sanitized_tiktok);
      
      if (!in_array('tiktok', $platforms)) {
          $platforms[] = 'tiktok';
      }
    } elseif ($platform === 'reddit') {
      $sanitized_reddit = [
        'pixel_id' => sanitize_text_field($data['pixelId'] ?? ''),
        'access_token' => sanitize_textarea_field($data['accessToken'] ?? ''),
        'test_code' => sanitize_text_field($data['testCode'] ?? ''),
        'events' => [],
      ];
      if (isset($data['events']) && is_array($data['events'])) {
          foreach ($data['events'] as $evt => $val) {
              $sanitized_reddit['events'][sanitize_text_field($evt)] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
          }
      }
      update_option('PixelOnWP_reddit_config', $sanitized_reddit);
      
      if (!in_array('reddit', $platforms)) {
          $platforms[] = 'reddit';
      }
    } elseif ($platform === 'pinterest') {
      $sanitized_pinterest = [
        'tag_id' => sanitize_text_field($data['tagId'] ?? ''),
        'ad_account_id' => sanitize_text_field($data['adAccountId'] ?? ''),
        'access_token' => sanitize_textarea_field($data['accessToken'] ?? ''),
        'enhanced_match' => isset($data['enhancedMatch']) ? filter_var($data['enhancedMatch'], FILTER_VALIDATE_BOOLEAN) : true,
        'first_party_cookies' => isset($data['firstPartyCookies']) ? filter_var($data['firstPartyCookies'], FILTER_VALIDATE_BOOLEAN) : true,
        'test_mode' => isset($data['testMode']) ? filter_var($data['testMode'], FILTER_VALIDATE_BOOLEAN) : false,
        'events' => [],
        'mappings' => []
      ];
      if (isset($data['events']) && is_array($data['events'])) {
          foreach ($data['events'] as $evt => $val) {
              $sanitized_pinterest['events'][sanitize_text_field($evt)] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
          }
      }
      if (isset($data['mappings']) && is_array($data['mappings'])) {
          foreach ($data['mappings'] as $evt => $val) {
              $sanitized_pinterest['mappings'][sanitize_text_field($evt)] = sanitize_text_field($val);
          }
      }
      update_option('PixelOnWP_pinterest_config', $sanitized_pinterest);
      if (!in_array('pinterest', $platforms)) {
          $platforms[] = 'pinterest';
      }
    } elseif ($platform === 'google') {
      $sanitized_events = [];
      if (isset($data['events']) && is_array($data['events'])) {
          foreach ($data['events'] as $evt) {
              if (is_array($evt) && isset($evt['name']) && isset($evt['label'])) {
                  $sanitized_events[] = [
                      'name' => sanitize_text_field($evt['name']),
                      'label' => sanitize_text_field($evt['label']),
                  ];
              }
          }
      }
      $sanitized_google = [
        'conversion_id' => sanitize_text_field($data['conversionId'] ?? ''),
        'conversion_label' => sanitize_text_field($data['conversionLabel'] ?? ''),
        'enhanced_conversions' => isset($data['enhancedConversions']) ? filter_var($data['enhancedConversions'], FILTER_VALIDATE_BOOLEAN) : false,
        'events' => $sanitized_events,
      ];
      update_option('PixelOnWP_google_config', $sanitized_google);
      
    } elseif ($platform === 'ga4') {
      $sanitized_events = [];
      if (isset($data['events']) && is_array($data['events'])) {
        foreach ($data['events'] as $evt => $val) {
          $sanitized_events[sanitize_key($evt)] = [
            'browser' => !empty($val['browser']) ? true : false,
            'server'  => !empty($val['server']) ? true : false
          ];
        }
      }
      $sanitized_ga4 = [
        'setup_type'     => sanitize_text_field($data['setupType'] ?? 'basic'),
        'measurement_id' => sanitize_text_field($data['measurementId'] ?? ''),
        'api_secret'     => sanitize_text_field($data['apiSecret'] ?? ''),
        'test_code'      => sanitize_text_field($data['testCode'] ?? ''),
        'events'         => $sanitized_events
      ];
      update_option('PixelOnWP_ga4_config', $sanitized_ga4);
      update_option('PixelOnWP_ga4_id', $sanitized_ga4['measurement_id']); // Keep backwards compatibility

      if (!in_array('ga4', $platforms)) {
          $platforms[] = 'ga4';
      }
    } else {
      wp_send_json_error(['message' => 'Invalid platform.']);
    }

    update_option('PixelOnWP_selected_platforms', $platforms);

    wp_send_json_success([
      'message' => ucfirst($platform) . ' configuration saved successfully.'
    ]);
  }

  /**
   * Handle removing a platform configuration progressively.
   *
   * @return void
   */
  public function remove_platform_config(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $platform = isset($_POST['platform']) ? sanitize_text_field(wp_unslash($_POST['platform'])) : '';
    
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (is_array($platforms) && in_array($platform, $platforms)) {
        $platforms = array_diff($platforms, [$platform]);
        update_option('PixelOnWP_selected_platforms', array_values($platforms));
    }
    
    if ($platform === 'facebook') {
      update_option('PixelOnWP_meta_config', ['pixel_id' => '', 'capi_token' => '', 'test_code' => '']);
    } elseif ($platform === 'tiktok') {
      update_option('PixelOnWP_tiktok_config', ['pixel_id' => '', 'access_token' => '', 'test_code' => '']);
    } elseif ($platform === 'reddit') {
      update_option('PixelOnWP_reddit_config', ['pixel_id' => '', 'access_token' => '', 'test_code' => '', 'events' => []]);
    } elseif ($platform === 'pinterest') {
      update_option('PixelOnWP_pinterest_config', ['tag_id' => '', 'ad_account_id' => '', 'access_token' => '', 'enhanced_match' => true, 'first_party_cookies' => true, 'test_mode' => false, 'events' => [], 'mappings' => []]);

    } elseif ($platform === 'ga4') {
      update_option('PixelOnWP_ga4_config', ['setup_type' => 'basic', 'measurement_id' => '', 'api_secret' => '', 'test_code' => '', 'events' => [], 'custom_events' => []]);
      update_option('PixelOnWP_ga4_id', '');
    } elseif ($platform === 'google') {
      update_option('PixelOnWP_google_config', ['conversion_id' => '', 'conversion_label' => '']);
    } else {
      wp_send_json_error(['message' => 'Invalid platform.']);
    }

    wp_send_json_success([
      'message' => ucfirst($platform) . ' configuration removed successfully.'
    ]);
  }

  /**
   * Handle saving the GTM Container ID.
   *
   * @return void
   */
  public function save_gtm(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $gtm_id = isset($_POST['gtm_id']) ? sanitize_text_field(wp_unslash($_POST['gtm_id'])) : '';
    
    update_option('PixelOnWP_gtm_id', $gtm_id);

    wp_send_json_success([
      'message' => 'GTM configuration saved successfully.'
    ]);
  }

  /**
   * Handle saving GA4 Measurement ID configuration.
   *
   * @return void
   */
  public function save_ga4(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $ga4_id = isset($_POST['ga4_id']) ? sanitize_text_field(wp_unslash($_POST['ga4_id'])) : '';
    
    update_option('PixelOnWP_ga4_id', $ga4_id);

    wp_send_json_success([
      'message' => 'GA4 configuration saved successfully.'
    ]);
  }

  /**
   * Handle saving GA4 Custom Events rules list.
   *
   * @return void
   */
  public function save_ga4_custom_events(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $raw_events = isset($_POST['events']) ? wp_unslash($_POST['events']) : '[]';
    $events = json_decode($raw_events, true);

    if (!is_array($events)) {
      wp_send_json_error(['message' => 'Invalid events format.'], 400);
    }

    $sanitized_events = [];
    foreach ($events as $event) {
      if (empty($event['name'])) {
        continue;
      }
      
      // Enforce strict GA4 snake_case formatting on key/event names
      $event_name = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($event['name'])));
      
      $sanitized_params = [];
      if (isset($event['parameters']) && is_array($event['parameters'])) {
        foreach ($event['parameters'] as $p) {
          if (!empty($p['key'])) {
            $param_key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($p['key'])));
            $sanitized_params[] = [
              'key' => $param_key,
              'value_type' => sanitize_text_field($p['value_type'] ?? 'static'),
              'value_source' => sanitize_text_field($p['value_source'] ?? '')
            ];
          }
        }
      }

      $sanitized_events[] = [
        'id' => sanitize_text_field($event['id'] ?? uniqid('ga4_evt_')),
        'name' => $event_name,
        'trigger_type' => sanitize_text_field($event['trigger_type'] ?? 'click'),
        'selector' => sanitize_text_field($event['selector'] ?? ''),
        'client_enabled' => !empty($event['client_enabled']) ? 1 : 0,
        'server_enabled' => !empty($event['server_enabled']) ? 1 : 0,
        'parameters' => $sanitized_params
      ];
    }

    update_option('PixelOnWP_ga4_custom_events', $sanitized_events);

    // Flush cache so changes reflect instantly
    $this->flush_all_caches();

    wp_send_json_success([
      'message' => 'GA4 Custom Events configured successfully.',
      'events' => $sanitized_events
    ]);
  }

  /**
   * Handle logging frontend events (like GA4) to the Diagnostics table.
   *
   * @return void
   */
  public function log_frontend_event(): void
  {
    // Validate request
    if (!isset($_POST['event_name'], $_POST['event_id'])) {
      wp_send_json_error();
    }

    global $wpdb;
    $table_event_logs = $wpdb->prefix . 'pixelonwp_event_logs';

    $event_name = sanitize_text_field(wp_unslash($_POST['event_name']));
    $event_id = sanitize_text_field(wp_unslash($_POST['event_id']));
    $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'success';
    $payload_raw = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : '{}';
    
    // Ensure payload is valid JSON before inserting
    json_decode($payload_raw);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $payload_raw = wp_json_encode(['error' => 'Invalid JSON payload received from frontend']);
    }

    $wpdb->insert(
      $table_event_logs,
      [
        'event_name' => $event_name,
        'event_id' => $event_id,
        'platform' => 'google',
        'payload' => $payload_raw,
        'status' => $status,
        'created_at' => current_time('mysql'),
      ],
      ['%s', '%s', '%s', '%s', '%s', '%s']
    );

    wp_send_json_success();
  }

  /**
   * Handle saving the Events Builder configuration.
   *
   * @return void
   */
  public function save_events_builder(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $datalayer_enabled = isset($_POST['datalayer_enabled']) ? filter_var($_POST['datalayer_enabled'], FILTER_VALIDATE_BOOLEAN) : true;
    
    $current = get_option('PixelOnWP_comprehensive_events', []);
    if (!is_array($current)) $current = [];
    $current['datalayer_enabled'] = $datalayer_enabled;
    
    update_option('PixelOnWP_comprehensive_events', $current);

    // Form Tracking Settings
    if (isset($_POST['form_tracking'])) {
        // Since $_POST might pass it as a JSON string from frontend depending on how we send it
        $form_tracking = json_decode(stripslashes($_POST['form_tracking']), true);
        if (is_array($form_tracking)) {
            $sanitized_form_tracking = array_map('sanitize_text_field', $form_tracking);
            update_option('PixelOnWP_form_tracking', $sanitized_form_tracking);
        }
    }

    wp_send_json_success([
      'message' => 'DataLayer configuration saved successfully.'
    ]);
  }

  /**
   * Fetch recent logs from database.
   *
   * @return void
   */
  public function get_logs(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    global $wpdb;
    $event_table = $wpdb->prefix . 'pixelonwp_event_logs';
    $fraud_table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    $level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : 'all';
    $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : 'all';
    $err_id_raw = isset($_POST['err_id']) ? sanitize_text_field($_POST['err_id']) : '';
    
    $event_where = "1=1";
    if ($level === 'error') {
      $event_where = "status = 'failed'";
    } elseif ($level === 'info' || $level === 'success') {
      $event_where = "status = 'success'";
    } elseif ($level === 'warning') {
      $event_where = "status = 'pending'";
    }
    
    if ($platform !== 'all' && $platform !== 'error') {
        if ($platform === 'facebook') {
            $event_where .= " AND (platform = 'facebook' OR platform = '' OR platform IS NULL)";
        } else {
            $event_where .= $wpdb->prepare(" AND platform = %s", $platform);
        }
    }

    $events = $wpdb->get_results("SELECT id, event_name, event_id, platform, payload, status, created_at FROM {$event_table} WHERE {$event_where} ORDER BY id DESC LIMIT 50", ARRAY_A);
    if (!is_array($events)) $events = [];
    
    // Explicitly fetch the requested err_id if it's missing from the top 50
    $has_specific = false;
    $target_numeric_id = 0;
    if (strpos($err_id_raw, 'evt_') === 0) {
        $target_numeric_id = (int) str_replace('evt_', '', $err_id_raw);
        foreach ($events as $ev) {
            if ((int)$ev['id'] === $target_numeric_id) {
                $has_specific = true;
                break;
            }
        }
        if (!$has_specific && $target_numeric_id > 0) {
            $specific_ev = $wpdb->get_row($wpdb->prepare("SELECT id, event_name, event_id, platform, payload, status, created_at FROM {$event_table} WHERE id = %d", $target_numeric_id), ARRAY_A);
            if ($specific_ev) {
                array_unshift($events, $specific_ev);
            }
        }
    }

    foreach ($events as &$ev) {
        $ev['uid'] = 'evt_' . $ev['id'];
        $ev['log_type'] = 'event';
    }

    $fraud_where = "1=1";
    if ($level === 'info' || $level === 'success' || ($platform !== 'all' && $platform !== 'error')) {
      $fraud_where = "1=0";
    }

    $frauds = $wpdb->get_results("SELECT id, reason as event_name, ip_address as event_id, 'system' as platform, request_data as payload, 'failed' as status, created_at FROM {$fraud_table} WHERE {$fraud_where} ORDER BY id DESC LIMIT 50", ARRAY_A);
    if (!is_array($frauds)) $frauds = [];
    foreach ($frauds as &$fr) {
        $fr['uid'] = 'frd_' . $fr['id'];
        $fr['log_type'] = 'diagnostic';
        $fr['event_id'] = 'IP: ' . $fr['event_id'];
    }

    $all_logs = array_merge($events, $frauds);
    usort($all_logs, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    wp_send_json_success(['logs' => array_slice($all_logs, 0, 100)]);
  }

  /**
   * Clear all logs from database.
   *
   * @return void
   */
  public function clear_logs_table(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';
    $wpdb->query("TRUNCATE TABLE {$table}");

    wp_send_json_success(['message' => 'Logs cleared successfully.']);
  }

  public function clear_website_cache(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $this->flush_all_caches();

    wp_send_json_success([
      'message' => 'All caches cleared successfully.'
    ]);
  }

  private function flush_all_caches(): void
  {
    // Clear WP Rocket cache
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }
    
    // Clear LiteSpeed cache
    if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
      \LiteSpeed_Cache_API::purge_all();
    } elseif (has_action('litespeed_purge_all')) {
      do_action('litespeed_purge_all');
    }

    // Clear W3 Total Cache
    if (function_exists('w3tc_flush_all')) {
      w3tc_flush_all();
    }

    // Clear WP Super Cache
    if (function_exists('wp_cache_clear_cache')) {
      wp_cache_clear_cache();
    }

    // Clear Autoptimize
    if (class_exists('autoptimizeCache')) {
      \autoptimizeCache::clearall();
    }
    
    // Clear Swift Performance
    if (class_exists('Swift_Performance_Cache')) {
      \Swift_Performance_Cache::clear_all_cache();
    }
    
    // Fallback: Clear WP object cache
    wp_cache_flush();
  }

  /**
   * Fetch dashboard live metrics.
   *
   * @return void
   */
  public function get_dashboard_stats(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    // Last 24 Hours
    $time_24h_ago = date('Y-m-d H:i:s', strtotime('-24 hours'));
    
    // Server Events (24H)
    $total_events = (int) $wpdb->get_var(
      $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE created_at >= %s", $time_24h_ago)
    );

    // CAPI Match Rate
    $success_events = (int) $wpdb->get_var(
      $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE (status = 'success' OR status = 'dispatched') AND created_at >= %s", $time_24h_ago)
    );
    
    $match_rate = $total_events > 0 ? round(($success_events / $total_events) * 100, 1) : 100;

    // Queue Failures
    $queue_failures = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'failed' OR status = 'pending'");

    // Integration Status
    $meta_config = get_option('PixelOnWP_meta_config', []);
    $meta_pixel = !empty($meta_config['pixel_id']) && !empty($meta_config['capi_token']) ? 'Healthy' : 'Inactive';
    
    $tiktok_config = get_option('PixelOnWP_tiktok_config', []);
    $tiktok = !empty($tiktok_config['pixel_id']) ? 'Healthy' : 'Inactive';

    $reddit_config = get_option('PixelOnWP_reddit_config', []);
    $reddit = !empty($reddit_config['pixel_id']) ? 'Healthy' : 'Inactive';
    
    $gtm_id = get_option('PixelOnWP_gtm_id', '');
    $gtm = !empty($gtm_id) ? 'Healthy' : 'Inactive';

    // Query platform-specific metrics
    $meta_24h = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE platform = 'facebook' AND created_at >= %s", $time_24h_ago));
    $meta_lifetime = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE platform = 'facebook'");

    $tiktok_24h = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE platform = 'tiktok' AND created_at >= %s", $time_24h_ago));
    $tiktok_lifetime = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE platform = 'tiktok'");

    $reddit_24h = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE platform = 'reddit' AND created_at >= %s", $time_24h_ago));
    $reddit_lifetime = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE platform = 'reddit'");

    $google_ads_24h = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE platform = 'google_ads' AND created_at >= %s", $time_24h_ago));
    $google_ads_lifetime = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE platform = 'google_ads'");

    $ga4_24h = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE (platform = 'ga4' OR platform = 'google') AND created_at >= %s", $time_24h_ago));
    $ga4_lifetime = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE (platform = 'ga4' OR platform = 'google')");
    
    $ga4_id = get_option('PixelOnWP_ga4_id', '');
    $ga4 = !empty($ga4_id) ? 'Healthy' : 'Inactive';

    $google_config = get_option('PixelOnWP_google_config', []);
    $google_ads = !empty($google_config['conversion_id']) ? 'Healthy' : 'Inactive';
    
    wp_send_json_success([
      'server_events' => number_format($total_events),
      'match_rate' => $match_rate . '%',
      'deduplication' => '100%',
      'queue_failures' => number_format($queue_failures),
      'integrations' => [
        'meta' => $meta_pixel,
        'tiktok' => $tiktok,
        'reddit' => $reddit,
        'gtm' => $gtm,
        'ga4' => $ga4,
        'google_ads' => $google_ads
      ],
      'metrics' => [
        'meta' => [
          '24h' => number_format($meta_24h),
          'lifetime' => number_format($meta_lifetime),
        ],
        'tiktok' => [
          '24h' => number_format($tiktok_24h),
          'lifetime' => number_format($tiktok_lifetime),
        ],
        'reddit' => [
          '24h' => number_format($reddit_24h),
          'lifetime' => number_format($reddit_lifetime),
        ],
        'google_ads' => [
          '24h' => number_format($google_ads_24h),
          'lifetime' => number_format($google_ads_lifetime),
        ],
        'ga4' => [
          '24h' => number_format($ga4_24h),
          'lifetime' => number_format($ga4_lifetime),
        ]
      ]
    ]);
  }


  /**
   * Retry failed queue events manually.
   *
   * @return void
   */
  public function retry_queue(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $results = ['success_count' => 0, 'failed_events' => []];
    if (class_exists('\\PixelOnWP\\Includes\\Queue\\PixelOnWP_Queue_Processor')) {
      $processor = new \PixelOnWP\Includes\Queue\PixelOnWP_Queue_Processor();
      $results = $processor->process_failed_queue();
    }

    if (!empty($results['failed_events'])) {
        wp_send_json_success([
            'message' => 'Queue processed with errors.',
            'has_errors' => true,
            'details' => $results
        ]);
    } else {
        wp_send_json_success([
            'message' => 'Queue processed successfully.',
            'has_errors' => false,
            'details' => $results
        ]);
    }
  }

  /**
   * Factory reset: clear all plugin options and logs.
   *
   * @return void
   */
  public function clear_all_data(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    global $wpdb;
    
    // Truncate Tables
    $table_logs = $wpdb->prefix . 'pixelonwp_event_logs';
    $table_fraud = $wpdb->prefix . 'pixelonwp_fraud_logs';
    $table_visitor = $wpdb->prefix . 'pixelonwp_visitor_intelligence';
    
    $wpdb->query("TRUNCATE TABLE {$table_logs}");
    $wpdb->query("TRUNCATE TABLE {$table_fraud}");
    
    // Also truncate visitor intelligence table (AI dummy data)
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_visitor}'") === $table_visitor) {
        $wpdb->query("TRUNCATE TABLE {$table_visitor}");
    }

    // Delete all options that start with PixelOnWP_
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'PixelOnWP_%'");
    
    // Also delete any other related options if they exist
    delete_option('pixelonwp_pixel_tracker_version');
    delete_option('pixelonwp_dummy_data_v2');
    
    // Clear all AI caches
    delete_transient('pixelonwp_ai_insights_cache');
    delete_transient('pixelonwp_ai_search_demand_cache');
    delete_transient('pixelonwp_ai_search_cache');
    delete_transient('pixelonwp_ai_fraud_cache');
    
    wp_cache_flush();

    wp_send_json_success(['message' => 'All data cleared.']);
  }

  /**
   * Fetch data for the Event Manager view.
   *
   * @return void
   */
  public function get_events_manager_data(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $settings = get_option('PixelOnWP_settings', []);
    $tracking_mode = $settings['tracking_mode'] ?? 'both';
    
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    $is_fb_active = is_array($platforms) && in_array('facebook', $platforms, true);
    $is_tt_active = is_array($platforms) && in_array('tiktok', $platforms, true);
    $is_reddit_active = is_array($platforms) && in_array('reddit', $platforms, true);
    $is_pinterest_active = is_array($platforms) && in_array('pinterest', $platforms, true);
    
    $active_meta_events = get_option('PixelOnWP_active_events', []);
    $active_params_config = get_option('PixelOnWP_active_params', []);
    
    global $wpdb;
    $table_logs = $wpdb->prefix . 'pixelonwp_event_logs';

    $categories = [
      'core' => [
        'title' => 'Core Events',
        'events' => [
          ['name' => 'page_view (Meta: PageView)', 'query' => 'PageView', 'key' => 'page_view', 'desc' => 'Fires on every page load.', 'params' => ['page_title', 'page_location', 'user_id', 'client_user_agent']]
        ]
      ],
      'ecommerce' => [
        'title' => 'E-Commerce Events',
        'events' => [
          ['name' => 'purchase (Meta: Purchase)', 'query' => 'Purchase', 'key' => 'purchase', 'desc' => 'Transaction completed.', 'params' => ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items', 'order_id']],
          ['name' => 'begin_checkout (Meta: InitiateCheckout)', 'query' => 'InitiateCheckout', 'key' => 'begin_checkout', 'desc' => 'User enters checkout flow.', 'params' => ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items']],
          ['name' => 'add_payment_info (Meta: AddPaymentInfo)', 'query' => 'AddPaymentInfo', 'key' => 'add_payment_info', 'desc' => 'User adds payment information.', 'params' => ['value', 'currency', 'content_category']],
          ['name' => 'add_to_cart (Meta: AddToCart)', 'query' => 'AddToCart', 'key' => 'add_to_cart', 'desc' => 'When user adds item to cart.', 'params' => ['value', 'currency', 'content_name', 'content_ids', 'content_type']],
          ['name' => 'view_item (Meta: ViewContent)', 'query' => 'ViewContent', 'key' => 'view_item', 'desc' => 'When user views a product.', 'params' => ['value', 'currency', 'content_name', 'content_ids', 'content_type']],
          ['name' => 'add_to_wishlist (Meta: AddToWishlist)', 'query' => 'AddToWishlist', 'key' => 'add_to_wishlist', 'desc' => 'When user saves an item to wishlist.', 'params' => ['value', 'currency', 'content_name', 'content_ids']],
          ['name' => 'place_an_order (Meta: PlaceAnOrder)', 'query' => 'PlaceAnOrder', 'key' => 'place_an_order', 'desc' => 'Order is placed.', 'params' => ['value', 'currency', 'content_name', 'content_ids', 'content_type', 'num_items', 'order_id']],
          ['name' => 'dynamic_status (Meta: DynamicStatus)', 'query' => 'DynamicStatus', 'key' => 'dynamic_status', 'desc' => 'Dynamically track WooCommerce status changes.', 'params' => ['status', 'order_id']]
        ]
      ],
      'lead' => [
        'title' => 'Engagement & Lead Events',
        'events' => [
          ['name' => 'generate_lead (Meta: Lead)', 'query' => 'Lead', 'key' => 'lead', 'desc' => 'Form submissions or lead generation.', 'params' => ['lead_id', 'form_name']],
          ['name' => 'contact (Meta: Contact)', 'query' => 'Contact', 'key' => 'contact', 'desc' => 'Fires on contact actions.', 'params' => ['contact_method']],
          ['name' => 'search (Meta: Search)', 'query' => 'Search', 'key' => 'search', 'desc' => 'Fires on site search.', 'params' => ['search_string']],
          ['name' => 'schedule (Meta: Schedule)', 'query' => 'Schedule', 'key' => 'schedule', 'desc' => 'Fires on appointments or bookings.', 'params' => ['appointment_time']],
          ['name' => 'subscribe (Meta: Subscribe)', 'query' => 'Subscribe', 'key' => 'subscribe', 'desc' => 'Fires on newsletters or memberships.', 'params' => ['subscription_type']],
          ['name' => 'file_download (Meta: Download)', 'query' => 'Download', 'key' => 'download', 'desc' => 'Fires on file downloads.', 'params' => ['file_name', 'file_type']]
        ]
      ],
      'auth' => [
        'title' => 'Authentication Events',
        'events' => [
          ['name' => 'sign_up (Meta: CompleteRegistration)', 'query' => 'CompleteRegistration', 'key' => 'complete_registration', 'desc' => 'Fires on user account creation.', 'params' => ['method']]
        ]
      ]
    ];

    $response_data = [];

    foreach ($categories as $cat_key => $cat_data) {
      $cat_events = [];
      foreach ($cat_data['events'] as $ev) {
        $event_key_lower = strtolower($ev['name']);
                // Check global enabled state
        $is_enabled = ($event_key_lower === 'place_an_order' || strpos($event_key_lower, 'placeanorder') !== false || strpos($event_key_lower, 'place_an_order') !== false) ? false : true;
        if (isset($active_meta_events[$event_key_lower])) {
          $is_enabled = $active_meta_events[$event_key_lower] === '1';
        }
         $is_active = false;
         if ($is_fb_active || $is_tt_active || $is_reddit_active || $is_pinterest_active) {
            if ($is_enabled) {
               $is_active = true;
            }
         }
        
        // Calculate Success Rate & Last Trigger for FB
      $query_name = isset($ev['query']) ? $ev['query'] : $ev['name'];
      
      $fb_total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'facebook'", $query_name));
      $fb_success = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'facebook' AND status = 'success'", $query_name));
      $fb_last_trigger = $wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$table_logs} WHERE event_name = %s AND platform = 'facebook' ORDER BY id DESC LIMIT 1", $query_name));

      $fb_success_rate = '-';
      if ($fb_total > 0) {
        $fb_success_rate = round(($fb_success / $fb_total) * 100) . '%';
      }
      $fb_time_diff = '-';
      $fb_has_recent = false;
      if ($fb_last_trigger) {
        $fb_time_diff = human_time_diff(strtotime($fb_last_trigger), current_time('timestamp')) . ' ago';
        if (current_time('timestamp') - strtotime($fb_last_trigger) <= 48 * HOUR_IN_SECONDS) {
          $fb_has_recent = true;
        }
      }
      
      // For TT
      $tt_query_name = $query_name === 'Purchase' ? 'CompletePurchase' : $query_name;
      
      $tt_total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'tiktok'", $tt_query_name));
      $tt_success = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'tiktok' AND status = 'success'", $tt_query_name));
      $tt_last_trigger = $wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$table_logs} WHERE event_name = %s AND platform = 'tiktok' ORDER BY id DESC LIMIT 1", $tt_query_name));

      $tt_success_rate = '-';
      if ($tt_total > 0) {
        $tt_success_rate = round(($tt_success / $tt_total) * 100) . '%';
      }
      $tt_time_diff = '-';
      $tt_has_recent = false;
      if ($tt_last_trigger) {
        $tt_time_diff = human_time_diff(strtotime($tt_last_trigger), current_time('timestamp')) . ' ago';
        if (current_time('timestamp') - strtotime($tt_last_trigger) <= 48 * HOUR_IN_SECONDS) {
          $tt_has_recent = true;
        }
      }

      // For Reddit
      $reddit_query_name = $query_name;
      if ($query_name === 'PageView') $reddit_query_name = 'PageVisit';
      
      $reddit_total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'reddit'", $reddit_query_name));
      $reddit_success = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'reddit' AND status = 'success'", $reddit_query_name));
      $reddit_last_trigger = $wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$table_logs} WHERE event_name = %s AND platform = 'reddit' ORDER BY id DESC LIMIT 1", $reddit_query_name));

      $reddit_success_rate = '-';
      if ($reddit_total > 0) {
        $reddit_success_rate = round(($reddit_success / $reddit_total) * 100) . '%';
      }
      $reddit_time_diff = '-';
      $reddit_has_recent = false;
      if ($reddit_last_trigger) {
        $reddit_time_diff = human_time_diff(strtotime($reddit_last_trigger), current_time('timestamp')) . ' ago';
        if (current_time('timestamp') - strtotime($reddit_last_trigger) <= 48 * HOUR_IN_SECONDS) {
          $reddit_has_recent = true;
        }
      }

      // For Pinterest
      $pin_config = get_option('PixelOnWP_pinterest_config', []);
      $pin_query_name = 'page_visit';
      if (!empty($pin_config['mappings'][$query_name])) {
          // Convert browser format mapping to CAPI format for log queries
          $browser_name = $pin_config['mappings'][$query_name];
          $b2c = ['pagevisit'=>'page_visit','addtocart'=>'add_to_cart','checkout'=>'checkout','search'=>'search','lead'=>'lead','signup'=>'signup','watchvideo'=>'watch_video','viewcategory'=>'view_category','initiatecheckout'=>'checkout'];
          $pin_query_name = isset($b2c[$browser_name]) ? $b2c[$browser_name] : $browser_name;
      } else {
          $pin_query_map = [
              'PageView' => 'page_visit',
              'Purchase' => 'checkout',
              'InitiateCheckout' => 'checkout',
              'AddToCart' => 'add_to_cart',
              'ViewContent' => 'page_visit',
              'Search' => 'search',
              'Lead' => 'lead',
              'CompleteRegistration' => 'signup',
              'Download' => 'lead',
              'Contact' => 'lead',
              'Schedule' => 'lead'
          ];
          $pin_query_name = isset($pin_query_map[$query_name]) ? $pin_query_map[$query_name] : strtolower($query_name);
      }

      $pin_total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'pinterest'", $pin_query_name));
      $pin_success = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_logs} WHERE event_name = %s AND platform = 'pinterest' AND status = 'success'", $pin_query_name));
      $pin_last_trigger = $wpdb->get_var($wpdb->prepare("SELECT created_at FROM {$table_logs} WHERE event_name = %s AND platform = 'pinterest' ORDER BY id DESC LIMIT 1", $pin_query_name));

      $pin_success_rate = '-';
      if ($pin_total > 0) {
        $pin_success_rate = round(($pin_success / $pin_total) * 100) . '%';
      }
      $pin_time_diff = '-';
      $pin_has_recent = false;
      if ($pin_last_trigger) {
        $pin_time_diff = human_time_diff(strtotime($pin_last_trigger), current_time('timestamp')) . ' ago';
        if (current_time('timestamp') - strtotime($pin_last_trigger) <= 48 * HOUR_IN_SECONDS) {
          $pin_has_recent = true;
        }
      }

        $event_params_state = isset($active_params_config[$event_key_lower]) ? $active_params_config[$event_key_lower] : [];
        $params_with_state = [];
        if (isset($ev['params']) && is_array($ev['params'])) {
          foreach ($ev['params'] as $param) {
             // default to enabled if not set
             $param_enabled = isset($event_params_state[$param]) ? $event_params_state[$param] === '1' : true;
             $params_with_state[] = [
               'name' => $param,
               'enabled' => $param_enabled
             ];
          }
        }

        $cat_events[] = [
          'name' => $ev['name'],
          'key' => $ev['key'],
          'desc' => $ev['desc'],
          'enabled' => $is_enabled,
          'active' => $is_active,
          'fb_active' => $is_fb_active,
          'fb_success' => $fb_success_rate,
          'fb_time' => $fb_time_diff,
          'fb_has_recent' => $fb_has_recent,
          'tt_active' => $is_tt_active,
          'tt_success' => $tt_success_rate,
          'tt_time' => $tt_time_diff,
          'tt_has_recent' => $tt_has_recent,
          'reddit_active' => $is_reddit_active,
          'reddit_success' => $reddit_success_rate,
          'reddit_time' => $reddit_time_diff,
          'reddit_has_recent' => $reddit_has_recent,
          'pinterest_active' => $is_pinterest_active,
          'pinterest_success' => $pin_success_rate,
          'pinterest_time' => $pin_time_diff,
          'pinterest_has_recent' => $pin_has_recent,
          'params' => $params_with_state
        ];
      }
      
      $response_data[] = [
        'id' => $cat_key,
        'title' => $cat_data['title'],
        'events' => $cat_events
      ];
    }

    wp_send_json_success(['categories' => $response_data]);
  }

  /**
   * Toggle event state
   */
  public function toggle_event_state(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $event_name = isset($_POST['event_name']) ? sanitize_text_field($_POST['event_name']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '0';

    if (empty($event_name)) {
      wp_send_json_error(['message' => 'Invalid event name.']);
    }

    $active_meta_events = get_option('PixelOnWP_active_events', []);
    $event_key_lower = strtolower($event_name);
    
    $active_meta_events[$event_key_lower] = $state === '1' ? '1' : '0';
    update_option('PixelOnWP_active_events', $active_meta_events);

    wp_send_json_success(['message' => 'Event status updated.']);
  }

  /**
   * Toggle event parameter state
   */
  public function toggle_event_param_state(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $event_name = isset($_POST['event_name']) ? sanitize_text_field($_POST['event_name']) : '';
    $param_name = isset($_POST['param_name']) ? sanitize_text_field($_POST['param_name']) : '';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '0';

    if (empty($event_name) || empty($param_name)) {
      wp_send_json_error(['message' => 'Invalid event or parameter name.']);
    }

    $active_params = get_option('PixelOnWP_active_params', []);
    $event_key_lower = strtolower($event_name);
    
    if (!isset($active_params[$event_key_lower])) {
       $active_params[$event_key_lower] = [];
    }
    
    $active_params[$event_key_lower][$param_name] = $state === '1' ? '1' : '0';
    update_option('PixelOnWP_active_params', $active_params);

    wp_send_json_success(['message' => 'Event parameter status updated.']);
  }

  /**
   * Save Fraud Settings
   */
  public function save_fraud_settings(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $raw_data = stripslashes($_POST['data'] ?? '{}');
    $data = json_decode($raw_data, true);

    if (is_array($data)) {
      update_option('PixelOnWP_fraud_settings', $data);
      wp_send_json_success(['message' => 'Fraud settings saved successfully.']);
    } else {
      wp_send_json_error(['message' => 'Invalid data format.']);
    }
  }

  /**
   * Manual Courier Lookup
   */
  public function courier_lookup(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    $phone = sanitize_text_field($_POST['phone'] ?? '');
    
    if (empty($phone)) {
       wp_send_json_error(['message' => 'Please provide a valid phone number.']);
    }

    if (class_exists('\\PixelOnWP\\Includes\\Security\\PixelOnWP_Fraud_Prevention')) {
       $fraud_prevention = new \PixelOnWP\Includes\Security\PixelOnWP_Fraud_Prevention();
       
       // Standardize phone
       $phone = preg_replace('/[^0-9]/', '', $phone);
       if (strlen($phone) > 11 && substr($phone, 0, 2) === '88') {
         $phone = substr($phone, 2);
       }

       $result = $fraud_prevention->get_courier_history($phone);
       
       $risk = 0;
       if ($result['total_parcels'] > 0) {
          $risk = round(($result['returned_parcels'] / $result['total_parcels']) * 100);
       }

       wp_send_json_success([
         'phone' => $result['phone_number'],
         'total' => $result['total_parcels'],
         'success' => $result['successful_deliveries'],
         'returned' => $result['returned_parcels'],
         'risk' => $risk,
         'breakdown' => $result['breakdown'] ?? []
       ]);
    } else {
       wp_send_json_error(['message' => 'Fraud module not loaded.']);
    }
  }

  /**
   * Get Recent Fraud Checks
   */
  public function get_recent_fraud_checks(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized action.'], 403);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'PixelOnWP_fraud_cache';
    
    // Fallback if table doesn't exist
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
        wp_send_json_success(['checks' => []]);
    }

    $results = $wpdb->get_results("SELECT phone_number, courier_data, created_at FROM {$table} ORDER BY created_at DESC LIMIT 50");
    
    $checks = [];
    foreach ($results as $row) {
       $data = json_decode($row->courier_data, true);
       if (!$data) continue;
       
       $risk = 0;
       if (($data['total_parcels'] ?? 0) > 0) {
          $risk = round((($data['returned_parcels'] ?? 0) / $data['total_parcels']) * 100);
       }
       
       $checks[] = [
          'phone' => $row->phone_number,
          'total' => $data['total_parcels'] ?? 0,
          'success' => $data['successful_deliveries'] ?? 0,
          'returned' => $data['returned_parcels'] ?? 0,
          'risk' => $risk,
          'breakdown' => $data['breakdown'] ?? [],
          'time' => human_time_diff(strtotime($row->created_at), current_time('timestamp')) . ' ago'
       ];
    }

    wp_send_json_success(['checks' => $checks]);
  }

  /**
   * AJAX handler to save a tracker rule.
   *
   * @since 1.2.0
   * @return void
   */
  public function save_tracker_rule(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized.'], 403);
    }

    $rule_id = isset($_POST['rule_id']) ? sanitize_text_field(wp_unslash($_POST['rule_id'])) : '';
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $trigger = isset($_POST['trigger_type']) ? sanitize_text_field(wp_unslash($_POST['trigger_type'])) : 'click';
    $selector = isset($_POST['selector']) ? sanitize_text_field(wp_unslash($_POST['selector'])) : '';
    $event_name = isset($_POST['event_name']) ? sanitize_text_field(wp_unslash($_POST['event_name'])) : '';
    $platforms = isset($_POST['platforms']) ? array_map('sanitize_text_field', (array)$_POST['platforms']) : [];
    $google_ads_label = isset($_POST['google_ads_label']) ? sanitize_text_field(wp_unslash($_POST['google_ads_label'])) : '';
    $parameters = isset($_POST['parameters']) ? json_decode(wp_unslash($_POST['parameters']), true) : [];

    $url_match_type = isset($_POST['url_match_type']) ? sanitize_text_field(wp_unslash($_POST['url_match_type'])) : 'all';
    $url_match_value = isset($_POST['url_match_value']) ? sanitize_text_field(wp_unslash($_POST['url_match_value'])) : '';

    if (empty($name) || empty($selector) || empty($event_name)) {
      wp_send_json_error(['message' => 'Required fields are missing.'], 400);
    }

    $rules = get_option('my_plugin_tracker_rules', []);
    if (!is_array($rules)) {
      $rules = [];
    }

    $sanitized_params = [];
    if (is_array($parameters)) {
      foreach ($parameters as $param) {
        if (!empty($param['key'])) {
          $sanitized_params[] = [
            'key' => sanitize_text_field($param['key']),
            'value_type' => sanitize_text_field($param['value_type'] ?? 'static'),
            'value_source' => sanitize_text_field($param['value_source'] ?? '')
          ];
        }
      }
    }

    $rule_data = [
      'id' => empty($rule_id) ? uniqid('rule_') : $rule_id,
      'name' => $name,
      'trigger_type' => $trigger,
      'selector' => $selector,
      'event_name' => $event_name,
      'platforms' => $platforms,
      'google_ads_label' => $google_ads_label,
      'parameters' => $sanitized_params,
      'url_match_type' => $url_match_type,
      'url_match_value' => $url_match_value,
      'active' => isset($_POST['active']) ? (int)$_POST['active'] : 1
    ];

    if (!empty($rule_id)) {
      // Edit existing
      $found = false;
      foreach ($rules as &$r) {
        if ($r['id'] === $rule_id) {
          $r = $rule_data;
          $found = true;
          break;
        }
      }
      if (!$found) {
        $rules[] = $rule_data;
      }
    } else {
      // New rule
      $rules[] = $rule_data;
    }

    update_option('my_plugin_tracker_rules', $rules);
    wp_send_json_success(['message' => 'Rule saved successfully.', 'rules' => $rules]);
  }

  /**
   * AJAX handler to delete a tracker rule.
   *
   * @since 1.2.0
   * @return void
   */
  public function delete_tracker_rule(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized.'], 403);
    }

    $rule_id = isset($_POST['rule_id']) ? sanitize_text_field(wp_unslash($_POST['rule_id'])) : '';
    if (empty($rule_id)) {
      wp_send_json_error(['message' => 'Missing rule ID.'], 400);
    }

    $rules = get_option('my_plugin_tracker_rules', []);
    if (!is_array($rules)) {
      $rules = [];
    }

    $rules = array_values(array_filter($rules, function($r) use ($rule_id) {
      return $r['id'] !== $rule_id;
    }));

    update_option('my_plugin_tracker_rules', $rules);
    wp_send_json_success(['message' => 'Rule deleted successfully.', 'rules' => $rules]);
  }

  /**
   * AJAX handler to toggle rule active state.
   *
   * @since 1.2.0
   * @return void
   */
  public function toggle_tracker_rule(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized.'], 403);
    }

    $rule_id = isset($_POST['rule_id']) ? sanitize_text_field(wp_unslash($_POST['rule_id'])) : '';
    $active = isset($_POST['active']) ? (int)$_POST['active'] : 0;

    if (empty($rule_id)) {
      wp_send_json_error(['message' => 'Missing rule ID.'], 400);
    }

    $rules = get_option('my_plugin_tracker_rules', []);
    if (is_array($rules)) {
      foreach ($rules as &$r) {
        if ($r['id'] === $rule_id) {
          $r['active'] = $active;
          break;
        }
      }
    }

    update_option('my_plugin_tracker_rules', $rules);
    wp_send_json_success(['message' => 'Rule state toggled successfully.', 'rules' => $rules]);
  }

  /**
   * AJAX handler to save tracker platforms.
   *
   * @since 1.2.0
   * @return void
   */
  public function save_tracker_platforms(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized.'], 403);
    }

    $platforms = [
      'fb_pixel_id' => isset($_POST['fb_pixel_id']) ? sanitize_text_field(wp_unslash($_POST['fb_pixel_id'])) : '',
      'fb_access_token' => isset($_POST['fb_access_token']) ? sanitize_text_field(wp_unslash($_POST['fb_access_token'])) : '',
      'tt_pixel_id' => isset($_POST['tt_pixel_id']) ? sanitize_text_field(wp_unslash($_POST['tt_pixel_id'])) : '',
      'google_ads_id' => isset($_POST['google_ads_id']) ? sanitize_text_field(wp_unslash($_POST['google_ads_id'])) : '',
      'ga4_measurement_id' => isset($_POST['ga4_measurement_id']) ? sanitize_text_field(wp_unslash($_POST['ga4_measurement_id'])) : ''
    ];

    update_option('my_plugin_tracker_platforms', $platforms);
    wp_send_json_success(['message' => 'Platform settings saved successfully.', 'platforms' => $platforms]);
  }

  /**
   * AJAX handler to toggle visual builder (Live Inspector/Debugger).
   *
   * @since 1.2.0
   * @return void
   */
  public function toggle_live_debugger(): void
  {
    check_ajax_referer('PixelOnWP_nonce', 'nonce');
    if (!current_user_can('manage_options') && !current_user_can('manage_woocommerce')) {
      wp_send_json_error(['message' => 'Unauthorized.'], 403);
    }

    $enabled = isset($_POST['enabled']) ? sanitize_text_field(wp_unslash($_POST['enabled'])) : '0';
    $settings = get_option('PixelOnWP_settings', []);
    if (!is_array($settings)) {
      $settings = [];
    }
    $settings['visual_builder_enabled'] = ($enabled === '1') ? '1' : '0';
    update_option('PixelOnWP_settings', $settings);
    update_option('pixelonwp_settings', $settings);

    wp_send_json_success([
      'message' => 'Live Inspector status updated.',
      'visual_builder_enabled' => $settings['visual_builder_enabled']
    ]);
  }
}
