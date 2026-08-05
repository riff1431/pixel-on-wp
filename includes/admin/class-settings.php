<?php
/**
 * Settings Management Class.
 *
 * @package PixelOnWP\Includes\Admin
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Settings Class.
 *
 * Handles retrieval, sanitization, and management of plugin settings and API credentials.
 *
 * @package PixelOnWP\Includes\Admin
 * @since 1.0.0
 */
class PixelOnWP_Settings
{

  /**
   * Option name in the WordPress database.
   *
   * @var string
   */
  private const OPTION_NAME = 'pixelonwp_settings';

  /**
   * Get all plugin settings with defaults applied.
   *
   * @since 1.0.0
   * @return array Array of plugin settings.
   */
  public static function get_settings(): array
  {
    $defaults = [
      'pixel_id' => '',
      'access_token' => '',
      'test_event_code' => '',
      'enable_browser_events' => '1',
      'enable_server_events' => '1',
      'advanced_matching' => '1',
      'event_deduplication' => '1',
      'datalayer_enabled' => '1',
    ];

    $settings = get_option(self::OPTION_NAME, []);

    return wp_parse_args($settings, $defaults);
  }

  /**
   * Get a specific setting value by key.
   *
   * @since 1.0.0
   * @param string $key     Setting key.
   * @param mixed  $default Optional default value if key not found.
   * @return mixed          Setting value or default.
   */
  public static function get(string $key, $default = null)
  {
    $settings = self::get_settings();
    return isset($settings[$key]) ? $settings[$key] : $default;
  }

  /**
   * Update plugin settings with sanitized values.
   *
   * @since 1.0.0
   * @param array $new_settings Array of raw settings to sanitize and save.
   * @return bool True on success, false on failure.
   */
  public static function update_settings(array $new_settings): bool
  {
    $current = self::get_settings();

    $sanitized = [
      'pixel_id' => isset($new_settings['pixel_id']) ? sanitize_text_field(wp_unslash($new_settings['pixel_id'])) : $current['pixel_id'],
      'access_token' => isset($new_settings['access_token']) ? sanitize_text_field(wp_unslash($new_settings['access_token'])) : $current['access_token'],
      'test_event_code' => isset($new_settings['test_event_code']) ? sanitize_text_field(wp_unslash($new_settings['test_event_code'])) : $current['test_event_code'],
      'enable_browser_events' => isset($new_settings['enable_browser_events']) ? '1' : '0',
      'enable_server_events' => isset($new_settings['enable_server_events']) ? '1' : '0',
      'advanced_matching' => isset($new_settings['advanced_matching']) ? '1' : '0',
      'event_deduplication' => isset($new_settings['event_deduplication']) ? '1' : '0',
      'datalayer_enabled' => isset($new_settings['datalayer_enabled']) ? '1' : '0',
    ];

    return update_option(self::OPTION_NAME, $sanitized);
  }
}