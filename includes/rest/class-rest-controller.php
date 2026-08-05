<?php
/**
 * REST API Controller Class.
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
 * PixelOnWP_REST_Controller Class.
 *
 * Handles incoming client-side event beacons and AJAX/REST tracking dispatch requests.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_REST_Controller
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
    $loader->add_action('rest_api_init', $this, 'register_rest_routes');
    $loader->add_action('wp_ajax_pixelonwp_track_event', $this, 'handle_ajax_tracking_event');
    $loader->add_action('wp_ajax_nopriv_pixelonwp_track_event', $this, 'handle_ajax_tracking_event');
  }

  /**
   * Register custom REST API endpoints.
   *
   * @since 1.0.0
   * @return void
   */
  public function register_rest_routes(): void
  {
    register_rest_route(
      'pixelonwp/v1',
      '/track',
      [
        'methods' => \WP_REST_Server::CREATABLE,
        'callback' => [$this, 'handle_rest_tracking_event'],
        'permission_callback' => '__return_true',
      ]
    );
  }

  /**
   * Handle incoming REST API tracking requests.
   *
   * @since 1.0.0
   * @param \WP_REST_Request $request REST request object.
   * @return \WP_REST_Response         Response object.
   */
  public function handle_rest_tracking_event(\WP_REST_Request $request): \WP_REST_Response
  {
    $params = $request->get_json_params();

    if (empty($params) || !isset($params['event_name'])) {
      return new \WP_REST_Response(['success' => false, 'message' => 'Invalid event payload.'], 400);
    }

    $result = $this->process_event($params);

    return new \WP_REST_Response($result, $result['success'] ? 200 : 400);
  }

  /**
   * Handle incoming AJAX tracking requests.
   *
   * @since 1.0.0
   * @return void
   */
  public function handle_ajax_tracking_event(): void
  {
    // Nonce check for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'pixelonwp_frontend_nonce')) {
      wp_send_json_error(['message' => 'Security check failed.']);
    }

    $event_name = isset($_POST['event_name']) ? sanitize_text_field(wp_unslash($_POST['event_name'])) : '';
    if (empty($event_name)) {
      wp_send_json_error(['message' => 'Missing event name.']);
    }

    $event_data = isset($_POST['event_data']) ? json_decode(wp_unslash($_POST['event_data']), true) : [];

    $payload = [
      'event_name' => $event_name,
      'event_data' => is_array($event_data) ? $event_data : [],
    ];

    $result = $this->process_event($payload);

    if ($result['success']) {
      wp_send_json_success($result);
    } else {
      wp_send_json_error($result);
    }
  }

  /**
   * Common event processing and Conversions API dispatch routine.
   *
   * @since 1.0.0
   * @param array $data Event data payload.
   * @return array      Processing result status.
   */
  private function process_event(array $data): array
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

    if ('1' !== $settings['enable_server_events']) {
      return ['success' => false, 'message' => 'Server events are disabled.'];
    }

    $event_name = sanitize_text_field($data['event_name']);
    $event_id = isset($data['event_id']) ? sanitize_text_field($data['event_id']) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::generate_event_id();

    $user_data = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Tracker::get_hashed_user_data(null);

    $custom_data = isset($data['event_data']) ? $data['event_data'] : [];

    $capi_payload = [
      'event_name' => $event_name,
      'event_id' => $event_id,
      'user_data' => $user_data,
      'custom_data' => $custom_data,
    ];

    if (class_exists('\\PixelOnWP\\Includes\\Capi\\PixelOnWP_Capi_Dispatcher')) {
      \PixelOnWP\Includes\Capi\PixelOnWP_Capi_Dispatcher::dispatch($capi_payload);
      $api_result = ['success' => true, 'response' => ['status' => 'dispatched']];
    } else {
      // Fallback
      $api_result = PixelOnWP_API_Client::send_event($capi_payload);
      
      $logger = new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
      $logger->log_event(
        $event_name,
        $event_id,
        $capi_payload,
        $api_result['success'] ? 'success' : 'failed'
      );
    }

    return $api_result;
  }
}