<?php
/**
 * Tracking Manager Class.
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
 * PixelOnWP_Tracking_Manager Class.
 *
 * Coordinates tracking sub-components including AJAX handlers, REST controllers, and event dispatchers.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Tracking_Manager
{

  /**
   * Register hooks with WordPress for all tracking subsystems.
   *
   * @since 1.0.0
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    // Initialize and register AJAX tracking handler
    $ajax_handler = new PixelOnWP_Ajax_Handler();
    if (method_exists($ajax_handler, 'register_hooks')) {
      $ajax_handler->register_hooks($loader);
    }

    // Initialize and register REST API controller
    $rest_controller = new PixelOnWP_REST_Controller();
    if (method_exists($rest_controller, 'register_hooks')) {
      $rest_controller->register_hooks($loader);
    }

    // Register background queue cron execution if needed
    $loader->add_action('pixelonwp_background_queue_cron', $this, 'process_background_queue');
  }

  /**
   * Process background queue events (e.g. retrying failed asynchronous requests).
   *
   * @since 1.0.0
   * @return void
   */
  public function process_background_queue(): void
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    // Fetch up to 10 failed or pending events to retry
    $failed_logs = $wpdb->get_results(
      $wpdb->prepare("SELECT * FROM {$table} WHERE status = %s AND retry_count < %d ORDER BY id ASC LIMIT 10", 'failed', 3),
      ARRAY_A
    );

    if (empty($failed_logs)) {
      return;
    }

    foreach ($failed_logs as $log) {
      $payload = json_decode($log['payload'], true);
      if (!is_array($payload)) {
        continue;
      }

      $api_result = PixelOnWP_API_Client::send_event($payload);
      $new_status = $api_result['success'] ? 'success' : 'failed';
      $retry_count = (int) $log['retry_count'] + 1;

      $wpdb->update(
        $table,
        [
          'status' => $new_status,
          'retry_count' => $retry_count,
        ],
        ['id' => $log['id']],
        ['%s', '%d'],
        ['%d']
      );
    }
  }
}