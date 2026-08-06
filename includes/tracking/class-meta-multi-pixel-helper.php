<?php
/**
 * Meta Multi-Pixel Helper.
 *
 * Provides normalization, migration for single-to-multi pixel setup,
 * and data sanitization routines.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.1
 */

namespace PixelOnWP\Includes\Tracking;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Class PixelOnWP_Meta_Multi_Pixel_Helper
 */
class PixelOnWP_Meta_Multi_Pixel_Helper
{
  /**
   * Get all active Meta pixel configurations normalized as an array of pixel objects.
   * Maintains backward compatibility for legacy single pixel settings.
   *
   * @return array Array of pixel configurations.
   */
  public static function get_pixels(): array
  {
    $meta_config = get_option('PixelOnWP_meta_config', []);

    if (!is_array($meta_config)) {
      return [];
    }

    $pixels = [];

    // Check if multi-pixel array exists
    if (isset($meta_config['pixels']) && is_array($meta_config['pixels'])) {
      foreach ($meta_config['pixels'] as $index => $p) {
        if (!is_array($p)) continue;
        $pixel_id = isset($p['pixel_id']) ? trim($p['pixel_id']) : (isset($p['pixelId']) ? trim($p['pixelId']) : '');
        if (empty($pixel_id)) continue;

        $capi_token = isset($p['capi_token']) ? trim($p['capi_token']) : (isset($p['conversionsApiToken']) ? trim($p['conversionsApiToken']) : '');
        $test_code  = isset($p['test_code']) ? trim($p['test_code']) : (isset($p['testEventCode']) ? trim($p['testEventCode']) : '');
        $setup_type = isset($p['setup_type']) ? $p['setup_type'] : (isset($p['setupType']) ? $p['setupType'] : (!empty($capi_token) ? 'advanced' : 'basic'));

        $pixels[] = [
          'id'                  => isset($p['id']) ? sanitize_key($p['id']) : 'pixel_' . ($index + 1),
          'pixel_id'            => $pixel_id,
          'capi_token'          => $capi_token,
          'test_code'           => $test_code,
          'setup_type'          => $setup_type,
          'events'              => isset($p['events']) && is_array($p['events']) ? $p['events'] : [],
        ];
      }
    }

    // Legacy single-pixel fallback migration
    if (empty($pixels)) {
      $legacy_pixel_id = isset($meta_config['pixel_id']) ? trim($meta_config['pixel_id']) : (isset($meta_config['pixelId']) ? trim($meta_config['pixelId']) : '');
      if (!empty($legacy_pixel_id)) {
        $legacy_capi_token = isset($meta_config['capi_token']) ? trim($meta_config['capi_token']) : (isset($meta_config['capiToken']) ? trim($meta_config['capiToken']) : '');
        $legacy_test_code  = isset($meta_config['test_code']) ? trim($meta_config['test_code']) : (isset($meta_config['testCode']) ? trim($meta_config['testCode']) : '');

        $pixels[] = [
          'id'         => 'pixel_1',
          'pixel_id'   => $legacy_pixel_id,
          'capi_token' => $legacy_capi_token,
          'test_code'  => $legacy_test_code,
          'setup_type' => !empty($legacy_capi_token) ? 'advanced' : 'basic',
          'events'     => isset($meta_config['events']) && is_array($meta_config['events']) ? $meta_config['events'] : [],
        ];
      }
    }

    return $pixels;
  }

  /**
   * Sanitize an array of pixel objects submitted from admin UI.
   *
   * @param array $pixels_raw Raw input array of pixel objects.
   * @return array Sanitized array of pixel configurations.
   */
  public static function sanitize_pixels(array $pixels_raw): array
  {
    $sanitized = [];

    foreach ($pixels_raw as $index => $p) {
      if (!is_array($p)) continue;

      $pixel_id = sanitize_text_field($p['pixelId'] ?? $p['pixel_id'] ?? '');
      if (empty($pixel_id)) continue;

      $capi_token = sanitize_textarea_field($p['conversionsApiToken'] ?? $p['capi_token'] ?? '');
      $test_code  = sanitize_text_field($p['testEventCode'] ?? $p['test_code'] ?? '');
      $setup_type = sanitize_text_field($p['setupType'] ?? $p['setup_type'] ?? 'basic');

      $events = [];
      if (isset($p['events']) && is_array($p['events'])) {
        foreach ($p['events'] as $evt => $val) {
          $events[sanitize_key($evt)] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }
      }

      $sanitized[] = [
        'id'         => sanitize_key($p['id'] ?? ('pixel_' . ($index + 1))),
        'pixel_id'   => $pixel_id,
        'capi_token' => $capi_token,
        'test_code'  => $test_code,
        'setup_type' => in_array($setup_type, ['basic', 'advanced'], true) ? $setup_type : 'basic',
        'events'     => $events,
      ];
    }

    return $sanitized;
  }
}
