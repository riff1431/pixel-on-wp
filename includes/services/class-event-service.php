<?php
/**
 * Event Service Class.
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
 * PixelOnWP_Event_Service Class.
 *
 * Provides high-level business logic wrappers and programmatic APIs for triggering tracking events
 * across internal plugin modules and third-party extensions.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Event_Service
{

  /**
   * Programmatically trigger and dispatch a tracking event using the Event Model and Dispatcher.
   *
   * @since 1.0.0
   * @param string $event_name Name of the tracking event (e.g., 'Lead', 'CompleteRegistration', 'CustomEvent').
   * @param array  $custom_data Event parameters and custom data.
   * @param string $event_id   Optional unique event ID for deduplication.
   * @return array             Result array containing dispatch status and event metadata.
   */
  public static function trigger_event(string $event_name, array $custom_data = [], string $event_id = ''): array
  {
    $event_model = new PixelOnWP_Event_Model($event_name, $custom_data, $event_id);
    $payload = $event_model->to_array();

    // 1. Output Browser Event Snippet if output buffering allows or direct echo
    PixelOnWP_Meta_Pixel::output_event($event_model->get_event_name(), $custom_data, $event_model->get_event_id());

    // 2. Send via API Client
    $api_result = PixelOnWP_API_Client::send_event($payload);

    // 3. Log event to database
    $logger = new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
    $logger->log_event(
      $event_model->get_event_name(),
      $event_model->get_event_id(),
      $payload,
      $api_result['success'] ? 'success' : 'failed'
    );

    return [
      'success' => isset($api_result['success']) ? $api_result['success'] : false,
      'event_id' => $event_model->get_event_id(),
      'event_name' => $event_model->get_event_name(),
      'server_res' => $api_result,
    ];
  }
}