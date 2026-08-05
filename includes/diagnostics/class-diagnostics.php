<?php
/**
 * Diagnostics and Health Check Class.
 *
 * @package PixelOnWP\Includes\Diagnostics
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Diagnostics;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Diagnostics Class.
 *
 * Performs system health checks, API token validations, PHP environment inspections, and connectivity tests.
 *
 * @package PixelOnWP\Includes\Diagnostics
 * @since 1.0.0
 */
class PixelOnWP_Diagnostics
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
    $loader->add_action('wp_ajax_pixelonwp_test_api_connection', $this, 'ajax_test_api_connection');
  }

  /**
   * Run full diagnostic health check suite.
   *
   * @since 1.0.0
   * @return array Array of diagnostic test results.
   */
  public static function run_health_checks(): array
  {
    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

    $checks = [
      'php_version' => [
        'label' => __('PHP Version', 'pixel-on-wp'),
        'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'pass' : 'fail',
        'message' => sprintf(__('Current PHP version: %s (Required: 7.4+)', 'pixel-on-wp'), PHP_VERSION),
      ],
      'wp_version' => [
        'label' => __('WordPress Version', 'pixel-on-wp'),
        'status' => version_compare(get_bloginfo('version'), '5.8', '>=') ? 'pass' : 'warning',
        'message' => sprintf(__('Current WordPress version: %s', 'pixel-on-wp'), get_bloginfo('version')),
      ],
      'curl_extension' => [
        'label' => __('cURL Extension', 'pixel-on-wp'),
        'status' => extension_loaded('curl') ? 'pass' : 'fail',
        'message' => extension_loaded('curl') ? __('cURL extension is active.', 'pixel-on-wp') : __('cURL extension is missing.', 'pixel-on-wp'),
      ],
      'ssl_support' => [
        'label' => __('SSL / HTTPS', 'pixel-on-wp'),
        'status' => is_ssl() ? 'pass' : 'warning',
        'message' => is_ssl() ? __('Site is running over HTTPS.', 'pixel-on-wp') : __('Site is not using HTTPS. Conversions API works best with secure connections.', 'pixel-on-wp'),
      ],
      'pixel_id_configured' => [
        'label' => __('Meta Pixel ID', 'pixel-on-wp'),
        'status' => !empty($settings['pixel_id']) ? 'pass' : 'fail',
        'message' => !empty($settings['pixel_id']) ? __('Meta Pixel ID is configured.', 'pixel-on-wp') : __('Meta Pixel ID is missing.', 'pixel-on-wp'),
      ],
      'access_token_configured' => [
        'label' => __('Meta Access Token', 'pixel-on-wp'),
        'status' => !empty($settings['access_token']) ? 'pass' : 'fail',
        'message' => !empty($settings['access_token']) ? __('CAPI Access Token is configured.', 'pixel-on-wp') : __('CAPI Access Token is missing.', 'pixel-on-wp'),
      ],
    ];

    return $checks;
  }

  /**
   * AJAX handler to test Meta API connectivity.
   *
   * @since 1.0.0
   * @return void
   */
  public function ajax_test_api_connection(): void
  {
    check_ajax_referer('pixelonwp_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $test_payload = [
      'event_name' => 'TestConnectionEvent',
      'event_time' => time(),
      'event_id' => 'pixelonwp_test_' . time(),
      'event_source_url' => home_url(),
      'action_source' => 'website',
      'user_data' => [
        'client_ip_address' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_ip(),
        'client_user_agent' => \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_user_agent(),
      ],
      'custom_data' => [
        'test_mode' => true,
      ],
    ];

    $result = \PixelOnWP\Includes\Tracking\PixelOnWP_API_Client::send_event($test_payload);

    if ($result['success']) {
      wp_send_json_success(['message' => __('API Connection Test Successful! Meta Graph API responded correctly.', 'pixel-on-wp'), 'response' => $result['response']]);
    } else {
      wp_send_json_error(['message' => __('API Connection Test Failed: ', 'pixel-on-wp') . (isset($result['error']) ? $result['error'] : 'Unknown error')]);
    }
  }
}