<?php
/**
 * Meta Conversions API Dispatcher.
 *
 * Formats and sends events to Facebook Graph API.
 *
 * @package PixelOnWP\Includes\Capi
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Capi;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Capi_Dispatcher
{
  /**
   * Dispatch an event to Meta CAPI.
   *
   * @param array $event_data The event payload to send.
   */
  public static function dispatch(array $event_data): void
  {
    $pixels = [];
    if (class_exists('\\PixelOnWP\\Includes\\Tracking\\PixelOnWP_Meta_Multi_Pixel_Helper')) {
      $pixels = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Multi_Pixel_Helper::get_pixels();
    } else {
      $meta_config = get_option('PixelOnWP_meta_config', []);
      $legacy_pixel_id = isset($meta_config['pixel_id']) ? trim($meta_config['pixel_id']) : '';
      $legacy_capi_token = isset($meta_config['capi_token']) ? trim($meta_config['capi_token']) : '';
      if (!empty($legacy_pixel_id) && !empty($legacy_capi_token)) {
        $pixels[] = [
          'pixel_id'   => $legacy_pixel_id,
          'capi_token' => $legacy_capi_token,
          'test_code'  => isset($meta_config['test_code']) ? trim($meta_config['test_code']) : '',
          'setup_type' => 'advanced',
        ];
      }
    }

    $capi_pixels = array_filter($pixels, function($p) {
      return !empty($p['pixel_id']) && !empty($p['capi_token']) && ($p['setup_type'] ?? 'advanced') === 'advanced';
    });

    if (empty($capi_pixels)) {
      if (class_exists('\\PixelOnWP\\Includes\\Core\\PixelOnWP_Logger')) {
        $logger = new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
        $logger->log_event($event_data['event_name'], $event_data['event_id'], [], 'skipped', 'facebook');
      }
      return;
    }

    // Format single shared payload preserving exact event_id and hashing logic across all pixels
    $formatted_event = self::format_payload($event_data);

    foreach ($capi_pixels as $p) {
      $pixel_id   = $p['pixel_id'];
      $capi_token = $p['capi_token'];
      $test_code  = $p['test_code'] ?? '';

      $payload = [
        'data' => [$formatted_event],
      ];

      if (!empty($test_code)) {
        $payload['test_event_code'] = $test_code;
      }

      $url = "https://graph.facebook.com/v19.0/{$pixel_id}/events?access_token={$capi_token}";

      $response = wp_remote_post($url, [
        'method'      => 'POST',
        'timeout'     => 15,
        'redirection' => 5,
        'httpversion' => '1.1',
        'blocking'    => false,
        'sslverify'   => false,
        'headers'     => [
          'Content-Type' => 'application/json',
        ],
        'body'        => wp_json_encode($payload),
      ]);

      // Log the result
      if (class_exists('\\PixelOnWP\\Includes\\Core\\PixelOnWP_Logger')) {
        $logger = new \PixelOnWP\Includes\Core\PixelOnWP_Logger();
        $status = is_wp_error($response) ? 'failed' : 'dispatched';
        $logger->log_event($event_data['event_name'], $event_data['event_id'], $payload, $status, 'facebook (' . $pixel_id . ')');
      }
    }
  }

  /**
   * Format the event payload strictly to Meta CAPI schema.
   */
  private static function format_payload(array $event_data): array
  {
    $ga4_event = $event_data['event_name'];
    $fb_mapped = \PixelOnWP\Includes\Tracking\PixelOnWP_Meta_Tracker::get_fb_event_data($ga4_event, $event_data['custom_data'] ?? []);
    $fb_event = $fb_mapped['event_name'];

    $formatted = [
      'event_name' => $fb_event,
      'event_time' => time(),
      'event_id' => $event_data['event_id'],
      'event_source_url' => self::get_current_url(),
      'action_source' => 'website',
      'user_data' => self::format_user_data($event_data['user_data'] ?? []),
    ];

    if (!empty($fb_mapped['custom_data'])) {
      $formatted['custom_data'] = $fb_mapped['custom_data'];
    }

    return $formatted;
  }

  /**
   * Hash and format user data for CAPI.
   */
  private static function format_user_data(array $user_data): array
  {
    $formatted = [];
    $hashable_keys = ['em', 'fn', 'ln', 'ph', 'ct', 'st', 'zp', 'country'];

    foreach ($hashable_keys as $key) {
      if (!empty($user_data[$key])) {
        // The data coming from get_hashed_user_data() is already hashed.
        // Wrap it in an array for Meta CAPI per specifications.
        $val = strtolower(trim($user_data[$key]));
        $formatted[$key] = [$val];
      }
    }

    $ip = '';
    if (!empty($user_data['client_ip_address'])) {
      $ip = $user_data['client_ip_address'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }

    if ($ip === '127.0.0.1' || $ip === '::1') {
      $ip = '104.28.254.74'; // Dummy public IP for local testing
    }

    if (!empty($ip)) {
      $formatted['client_ip_address'] = $ip;
    }

    $ua = '';
    if (!empty($user_data['client_user_agent'])) {
      $ua = $user_data['client_user_agent'];
    } elseif (!empty($_SERVER['HTTP_USER_AGENT'])) {
      $ua = $_SERVER['HTTP_USER_AGENT'];
    }
    if (!empty($ua)) {
      $formatted['client_user_agent'] = $ua;
    }

    if (!empty($user_data['fbc'])) {
      $formatted['fbc'] = $user_data['fbc'];
    } elseif (!empty($_COOKIE['_fbc'])) {
      $formatted['fbc'] = sanitize_text_field(wp_unslash($_COOKIE['_fbc']));
    }

    if (!empty($user_data['fbp'])) {
      $formatted['fbp'] = $user_data['fbp'];
    } elseif (!empty($_COOKIE['_fbp'])) {
      $formatted['fbp'] = sanitize_text_field(wp_unslash($_COOKIE['_fbp']));
    }

    return $formatted;
  }

  /**
   * Get current URL.
   */
  private static function get_current_url(): string
  {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName . $_SERVER['REQUEST_URI'];
  }
}
