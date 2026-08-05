<?php
/**
 * Meta Conversions API (CAPI) Dispatcher Class.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Tracking;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Meta_CAPI Class.
 *
 * Constructs and dispatches server-side event payloads to the Meta Graph API Conversions API.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Meta_CAPI
{

  /**
   * Send an event to Meta Conversions API.
   *
   * @since 1.0.0
   * @param string $event_name Meta event name (e.g., 'Purchase', 'AddToCart').
   * @param array  $custom_data Event parameters and custom data.
   * @param string $event_id   Unique event ID for deduplication.
   * @return array             API response status and body.
   */
  public static function send_event(string $event_name, array $custom_data = [], string $event_id = ''): array
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

    if ('1' !== $settings['enable_server_events']) {
      return [
        'success' => false,
        'message' => __('Server-side tracking is disabled.', 'pixel-on-wp'),
      ];
    }

    $event_name = sanitize_text_field($event_name);
    $event_id = !empty($event_id) ? sanitize_text_field($event_id) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::generate_event_id();

    $client_ip = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_ip();
    $user_agent = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_user_agent();

    $fbp = isset($_COOKIE['_fbp']) ? sanitize_text_field(wp_unslash($_COOKIE['_fbp'])) : '';
    $fbc = isset($_COOKIE['_fbc']) ? sanitize_text_field(wp_unslash($_COOKIE['_fbc'])) : '';

    $user_data = [
      'client_ip_address' => $client_ip,
      'client_user_agent' => $user_agent,
    ];

    if (!empty($fbp)) {
      $user_data['fbp'] = $fbp;
    }
    if (!empty($fbc)) {
      $user_data['fbc'] = $fbc;
    }

    // Advanced Matching for logged in users
    if (is_user_logged_in() && '1' === $settings['advanced_matching']) {
      $current_user = wp_get_current_user();
      if (!empty($current_user->user_email)) {
        $user_data['em'] = [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::hash_user_data($current_user->user_email, 'email')];
      }
      if (!empty($current_user->user_firstname)) {
        $user_data['fn'] = [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::hash_user_data($current_user->user_firstname, 'name')];
      }
      if (!empty($current_user->user_lastname)) {
        $user_data['ln'] = [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::hash_user_data($current_user->user_lastname, 'name')];
      }
    }

    $payload = [
      'event_name' => $event_name,
      'event_time' => time(),
      'event_id' => $event_id,
      'event_source_url' => esc_url_raw(isset($_SERVER['HTTP_REFERER']) ? wp_unslash($_SERVER['HTTP_REFERER']) : home_url()),
      'action_source' => 'website',
      'user_data' => $user_data,
      'custom_data' => $custom_data,
    ];

    // Send via API Client
    $api_result = PixelOnWP_API_Client::send_event($payload);

    // Log to database
    $logger = new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
    $logger->log_event(
      $event_name,
      $event_id,
      $payload,
      $api_result['success'] ? 'success' : 'failed'
    );

    return $api_result;
  }
}