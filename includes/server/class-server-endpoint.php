<?php
/**
 * Server-Side Tracking Endpoint Handler Class.
 *
 * @package PixelOnWP\Includes\Server
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Server;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Server_Endpoint Class.
 *
 * Handles custom webhook endpoints and server-to-server tracking integrations.
 *
 * @package PixelOnWP\Includes\Server
 * @since 1.0.0
 */
class PixelOnWP_Server_Endpoint
{

  /**
   * Register hooks with WordPress.
   *
   * @since 1.0.0
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    $loader->add_action('rest_api_init', $this, 'register_server_endpoints');
  }

  /**
   * Register custom REST API server-side webhook endpoints.
   *
   * @since 1.0.0
   * @return void
   */
  public function register_server_endpoints(): void
  {
    register_rest_route(
      'pixelonwp/v1',
      '/webhook',
      [
        'methods' => \WP_REST_Server::CREATABLE,
        'callback' => [$this, 'handle_webhook_request'],
        'permission_callback' => [$this, 'verify_webhook_permission'],
      ]
    );

    $custom_route = get_option('PixelOnWP_custom_route', 'pixelonwp/v1/collect');
    // Ensure we strip wp-json/ if present
    $custom_route = ltrim(str_replace('wp-json/', '', $custom_route), '/');
    
    // Split into namespace and route
    $parts = explode('/', $custom_route, 2);
    $namespace = $parts[0] ?? 'pixelonwp/v1';
    $route = isset($parts[1]) ? '/' . $parts[1] : '/collect';
    
    if (strpos($namespace, '/') === false && isset($parts[1])) {
       // if it's like pixelonwp/v1/collect, explode by / gives wpt and v1/collect
       // let's do this differently
       $last_slash = strrpos($custom_route, '/');
       if ($last_slash !== false) {
           $namespace = substr($custom_route, 0, $last_slash);
           $route = substr($custom_route, $last_slash);
       }
    }

    register_rest_route(
      $namespace,
      $route,
      [
        'methods' => \WP_REST_Server::CREATABLE,
        'callback' => [$this, 'handle_collect_request'],
        'permission_callback' => '__return_true', // Public endpoint for frontend tracking
      ]
    );
  }

  /**
   * Verify incoming webhook request permissions via secret token header.
   *
   * @since 1.0.0
   * @param \WP_REST_Request $request REST request object.
   * @return bool True if authorized, false otherwise.
   */
  public function verify_webhook_permission(\WP_REST_Request $request): bool
  {
    $auth_header = $request->get_header('X-WPT-Webhook-Secret');

    if (empty($auth_header)) {
      $auth_header = $request->get_param('webhook_secret');
    }

    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();
    $configured_secret = isset($settings['webhook_secret']) ? trim($settings['webhook_secret']) : '';

    // If no secret is configured, deny public unauthenticated webhooks by default for security
    if (empty($configured_secret)) {
      return current_user_can('manage_options');
    }

    return hash_equals($configured_secret, (string) $auth_header);
  }

  /**
   * Handle incoming server-side webhook tracking payloads.
   *
   * @since 1.0.0
   * @param \WP_REST_Request $request REST request object.
   * @return \WP_REST_Response         Response object.
   */
  public function handle_webhook_request(\WP_REST_Request $request): \WP_REST_Response
  {
    $params = $request->get_json_params();

    if (empty($params) || !isset($params['event_name'])) {
      return new \WP_REST_Response([
        'success' => false,
        'message' => __('Invalid or empty webhook payload.', 'pixel-on-wp'),
      ], 400);
    }

    $event_name = sanitize_text_field($params['event_name']);
    $event_id = isset($params['event_id']) ? sanitize_text_field($params['event_id']) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::generate_event_id('pixelonwp_wh_');

    $client_ip = isset($params['client_ip_address']) ? sanitize_text_field($params['client_ip_address']) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_ip();
    $user_agent = isset($params['client_user_agent']) ? sanitize_text_field($params['client_user_agent']) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_user_agent();

    $user_data = [
      'client_ip_address' => $client_ip,
      'client_user_agent' => $user_agent,
    ];

    if (isset($params['user_data']) && is_array($params['user_data'])) {
      foreach ($params['user_data'] as $key => $val) {
        $sanitized_key = sanitize_text_field($key);
        if (in_array($sanitized_key, ['em', 'fn', 'ln', 'ph', 'ct', 'st', 'zp', 'country'], true) && is_string($val)) {
          // Hash PII fields if unhashed
          $user_data[$sanitized_key] = [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::hash_user_data($val, $sanitized_key === 'em' ? 'email' : ($sanitized_key === 'ph' ? 'phone' : 'name'))];
        } else {
          $user_data[$sanitized_key] = sanitize_text_field($val);
        }
      }
    }

    $capi_payload = [
      'event_name' => $event_name,
      'event_time' => isset($params['event_time']) ? absint($params['event_time']) : time(),
      'event_id' => $event_id,
      'event_source_url' => esc_url_raw(isset($params['event_source_url']) ? $params['event_source_url'] : home_url()),
      'action_source' => isset($params['action_source']) ? sanitize_text_field($params['action_source']) : 'system_generated',
      'user_data' => $user_data,
      'custom_data' => isset($params['custom_data']) && is_array($params['custom_data']) ? $params['custom_data'] : [],
    ];

    $api_result = PixelOnWP_API_Client::send_event($capi_payload);

    $logger = new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
    $logger->log_event(
      $event_name,
      $event_id,
      $capi_payload,
      $api_result['success'] ? 'success' : 'failed'
    );

    if ($api_result['success']) {
      return new \WP_REST_Response([
        'success' => true,
        'message' => __('Webhook event processed and sent to CAPI successfully.', 'pixel-on-wp'),
        'response' => $api_result['response'],
      ], 200);
    }

    return new \WP_REST_Response([
      'success' => false,
      'message' => __('Webhook CAPI dispatch failed.', 'pixel-on-wp'),
      'error' => isset($api_result['error']) ? $api_result['error'] : 'Unknown error',
    ], 500);
  }

  /**
   * Handle first-party collection endpoint for ITP bypass.
   *
   * @since 1.0.0
   * @param \WP_REST_Request $request REST request object.
   * @return \WP_REST_Response         Response object.
   */
  public function handle_collect_request(\WP_REST_Request $request): \WP_REST_Response
  {
    $params = $request->get_json_params();

    if (empty($params) || !isset($params['event'])) {
      return new \WP_REST_Response([
        'success' => false,
        'message' => __('Invalid payload.', 'pixel-on-wp'),
      ], 400);
    }

    $event_name = sanitize_text_field($params['event']);
    
    // Check if event is enabled
    $active_events = get_option('PixelOnWP_active_events', []);
    $event_key = strtolower($event_name);
    if ($event_key === 'completepurchase' || $event_key === 'purchase' || $event_key === 'completepayment') $event_key = 'purchase';
    if ($event_key === 'submitform' || $event_key === 'lead') $event_key = 'lead';
    
    if (isset($active_events[$event_key]) && $active_events[$event_key] !== '1') {
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Event is disabled via dashboard. Skipped.',
        ], 200);
    }

    // Map standard dataLayer events to Meta equivalents
    $event_map = [
      'generate_lead' => 'Lead',
      'contact' => 'Contact',
      'submit_form' => 'Lead',
      'sign_up' => 'CompleteRegistration',
      'schedule' => 'Schedule',
      'search' => 'Search',
      'add_to_wishlist' => 'AddToWishlist',
      'begin_checkout' => 'InitiateCheckout',
      'purchase' => 'Purchase',
      'add_to_cart' => 'AddToCart',
      'view_item' => 'ViewContent',
      'view_cart' => 'ViewCart'
    ];
    
    $fb_event_name = isset($event_map[$event_name]) ? $event_map[$event_name] : $event_name;
    $event_id = isset($params['event_id']) ? sanitize_text_field($params['event_id']) : 'evt_' . wp_generate_uuid4();

    $user_data = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Tracker::get_hashed_user_data(null);

    $event_data = [
      'event_name' => $fb_event_name,
      'event_id' => $event_id,
      'user_data' => $user_data,
    ];

    // Extract custom data if provided
    $custom_data = [];
    if (isset($params['custom_data']) && is_array($params['custom_data'])) {
      $custom_data = $params['custom_data'];
    } else {
      // It's a GA4 payload
      $ecom = isset($params['ecommerce']) ? $params['ecommerce'] : $params;
      
      if (isset($ecom['value'])) {
        $custom_data['value'] = (float) $ecom['value'];
      } elseif (isset($params['value'])) {
        $custom_data['value'] = (float) $params['value'];
      }
      
      if (isset($ecom['currency'])) {
        $custom_data['currency'] = sanitize_text_field($ecom['currency']);
      } elseif (isset($params['currency'])) {
        $custom_data['currency'] = sanitize_text_field($params['currency']);
      }
      
      if (isset($ecom['items']) && is_array($ecom['items'])) {
        $custom_data['items'] = $ecom['items'];
      }
    }
    
    if (!empty($custom_data)) {
      $event_data['custom_data'] = $custom_data;
    }

    if (class_exists('\\PixelOnWP\\Includes\\Capi\\PixelOnWP_Capi_Dispatcher')) {
      \PixelOnWP\Includes\Capi\PixelOnWP_Capi_Dispatcher::dispatch($event_data);
    }
    
    // 2. TikTok Events API Dispatch
    $platforms = get_option('PixelOnWP_selected_platforms', []);
    if (in_array('tiktok', $platforms, true)) {
        if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_TikTok_Tracker')) {
            // Note: Pass original event_name (like add_to_cart) to allow the tracker's own internal maps to resolve
            \PixelOnWP\Includes\Tracking\PixelOnWP_TikTok_Tracker::dispatch_tt_server_event_static($event_name, $event_id, $custom_data, null, $user_data);
        }
    }

    // Reddit Conversions API Dispatch
    if (in_array('reddit', $platforms, true)) {
        if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Reddit_Tracker')) {
            \PixelOnWP\Includes\Tracking\PixelOnWP_Reddit_Tracker::dispatch_reddit_server_event_static($event_name, $event_id, $custom_data, null, $user_data);
        }
    }
    // Pinterest Conversions API Dispatch
    if (in_array('pinterest', $platforms, true)) {
        if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Pinterest_Tracker')) {
            \PixelOnWP\Includes\Tracking\PixelOnWP_Pinterest_Tracker::dispatch_pinterest_server_event_static($event_name, $event_id, $custom_data, null, $user_data);
        }
    }
    if (class_exists('\\PixelOnWP\\Includes\\Platforms\\GoogleAnalytics\\PixelOnWP_GA4_Server_Tracker')) {
        $ga4_options = \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Admin_Options::get_options();
        $ga4_events_control = $ga4_options['events'] ?? [];
        
        $should_dispatch_server = false;
        // Check if event is a custom event configured for server-side
        $ga4_custom_events = get_option('PixelOnWP_ga4_custom_events', []);
        foreach ($ga4_custom_events as $evt) {
            if ($evt['name'] === $event_name && !empty($evt['server_enabled'])) {
                $should_dispatch_server = true;
                break;
            }
        }
        
        // Check standard events control settings
        if (isset($ga4_events_control[$event_name])) {
            $should_dispatch_server = !empty($ga4_events_control[$event_name]['server']);
        } else {
            $standard_ga4_events = [
                'view_item_list', 'select_item', 'view_item', 'add_to_cart', 'remove_from_cart', 'view_cart',
                'begin_checkout', 'add_shipping_info', 'add_payment_info', 'purchase', 'refund',
                'view_promotion', 'select_promotion', 'begin_trial', 'subscribe',
                'generate_lead', 'contact', 'schedule', 'search', 'select_content', 'share',
                'file_download', 'video_start', 'video_progress', 'video_complete', 'sign_up', 'login'
            ];
            if (in_array($event_name, $standard_ga4_events, true)) {
                $should_dispatch_server = true;
            }
        }

        if ($should_dispatch_server) {
            \PixelOnWP\Includes\Platforms\GoogleAnalytics\PixelOnWP_GA4_Server_Tracker::dispatch($event_name, $event_id, $custom_data, $user_data);
        }
    }



    return new \WP_REST_Response(['success' => true], 200);
  }
}