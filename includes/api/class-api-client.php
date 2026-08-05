<?php
/**
 * Meta Conversions API Client Class.
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
 * PixelOnWP_API_Client Class.
 *
 * Handles HTTP requests to the Meta Graph API for Server-Side tracking (Conversions API).
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_API_Client
{

  /**
   * Send event payload to Meta Conversions API.
   *
   * @since 1.0.0
   * @param array $payload Event payload data.
   * @return array        Response array with 'success' and 'response' or 'error'.
   */
  public static function send_event(array $payload): array
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

    $pixel_id = trim($settings['pixel_id']);
    $access_token = trim($settings['access_token']);

    if (empty($pixel_id) || empty($access_token)) {
      return [
        'success' => false,
        'error' => __('Meta Pixel ID or Access Token is missing.', 'pixel-on-wp'),
      ];
    }

    $url = 'https://graph.facebook.com/v18.0/' . sanitize_text_field($pixel_id) . '/events?access_token=' . sanitize_text_field($access_token);

    // Include test_event_code if configured
    if (!empty($settings['test_event_code'])) {
      if (!isset($payload['test_event_code'])) {
        $payload['test_event_code'] = sanitize_text_field($settings['test_event_code']);
      }
    }

    $body = wp_json_encode([
      'data' => [$payload],
    ]);

    $args = [
      'body' => $body,
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'timeout' => 15,
      'blocking' => false,
      'sslverify' => true,
    ];

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
      return [
        'success' => false,
        'error' => $response->get_error_message(),
      ];
    }

    // Since this is non-blocking, we assume success upon successful dispatch
    return [
      'success' => true,
      'response' => ['status' => 'dispatched'],
    ];
  }
}