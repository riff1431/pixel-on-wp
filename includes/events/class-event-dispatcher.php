<?php
/**
 * Event Dispatcher Class.
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
 * PixelOnWP_Event_Dispatcher Class.
 *
 * Manages the unified dispatching of events to both Browser Pixel and Server-Side CAPI simultaneously.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Event_Dispatcher
{

  /**
   * Dispatch custom or standard event to both browser and server channels.
   *
   * @since 1.0.0
   * @param string $event_name Name of the tracking event (e.g., 'Lead', 'CompleteRegistration', 'CustomEvent').
   * @param array  $params     Event parameter array.
   * @param string $event_id   Optional unique event ID for deduplication.
   * @return array             Result array containing status of browser and server dispatches.
   */
  public static function dispatch(string $event_name, array $params = [], string $event_id = ''): array
  {
    $event_name = sanitize_text_field($event_name);
    $event_id = !empty($event_id) ? sanitize_text_field($event_id) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::generate_event_id();

    // 1. Output Browser Event Snippet
    PixelOnWP_Meta_Pixel::output_event($event_name, $params, $event_id);

    // 2. Dispatch Server-Side CAPI Event
    $capi_result = PixelOnWP_Meta_CAPI::send_event($event_name, $params, $event_id);

    return [
      'success' => isset($capi_result['success']) ? $capi_result['success'] : false,
      'event_id' => $event_id,
      'event_name' => $event_name,
      'server_res' => $capi_result,
    ];
  }
}