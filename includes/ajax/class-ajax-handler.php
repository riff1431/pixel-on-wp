<?php
/**
 * AJAX Handler Class for Tracking Beacons.
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
 * PixelOnWP_Ajax_Handler Class.
 *
 * Manages asynchronous client-side tracking event endpoints via WordPress admin-ajax.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Ajax_Handler
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
    $loader->add_action('wp_ajax_pixelonwp_send_beacon', $this, 'handle_beacon');
    $loader->add_action('wp_ajax_nopriv_pixelonwp_send_beacon', $this, 'handle_beacon');
  }

  /**
   * Handle incoming AJAX beacon requests from client-side tracking scripts.
   *
   * @since 1.0.0
   * @return void
   */
  public function handle_beacon(): void
  {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'pixelonwp_frontend_nonce')) {
      wp_send_json_error(['message' => __('Security verification failed.', 'pixel-on-wp')], 403);
    }

    $event_name = isset($_POST['event_name']) ? sanitize_text_field(wp_unslash($_POST['event_name'])) : '';
    if (empty($event_name)) {
      wp_send_json_error(['message' => __('Missing event name.', 'pixel-on-wp')], 400);
    }

    $event_id = isset($_POST['event_id']) ? sanitize_text_field(wp_unslash($_POST['event_id'])) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::generate_event_id();

    $raw_data = isset($_POST['event_data']) ? wp_unslash($_POST['event_data']) : '';
    $event_data = is_string($raw_data) ? json_decode($raw_data, true) : [];

    $payload = [
      'event_name' => $event_name,
      'event_id' => $event_id,
      'event_data' => is_array($event_data) ? $event_data : [],
    ];

    // Process via REST/Tracking Controller mechanism
    $rest_controller = new PixelOnWP_REST_Controller();

    // Use reflection or direct CAPI dispatch via API Client
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

    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

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

    $capi_payload = [
      'event_name' => $event_name,
      'event_time' => time(),
      'event_id' => $event_id,
      'event_source_url' => esc_url_raw(isset($_SERVER['HTTP_REFERER']) ? wp_unslash($_SERVER['HTTP_REFERER']) : home_url()),
      'action_source' => 'website',
      'user_data' => $user_data,
      'custom_data' => $payload['event_data'],
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
      wp_send_json_success($api_result);
    } else {
      wp_send_json_error($api_result);
    }
  }
}