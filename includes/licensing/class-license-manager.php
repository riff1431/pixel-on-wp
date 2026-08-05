<?php
/**
 * License Manager Class.
 *
 * @package PixelOnWP\Includes\Licensing
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Licensing;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_License_Manager Class.
 *
 * Handles license key activation, remote verification API requests, update notices, and feature gating.
 *
 * @package PixelOnWP\Includes\Licensing
 * @since 1.0.0
 */
class PixelOnWP_License_Manager
{

  /**
   * Option name for storing license data.
   */
  const LICENSE_OPTION_KEY = 'pixelonwp_license_data';

  /**
   * Remote licensing server endpoint URL.
   */
  const API_ENDPOINT = 'https://api.wppixeltracker.com/v1/license';

  /**
   * Register hooks with WordPress.
   *
   * @since 1.0.0
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    $loader->add_action('wp_ajax_pixelonwp_activate_license', $this, 'ajax_activate_license');
    $loader->add_action('wp_ajax_pixelonwp_deactivate_license', $this, 'ajax_deactivate_license');

    // Schedule daily license background check
    if (!wp_next_scheduled('pixelonwp_daily_license_check')) {
      wp_schedule_event(time(), 'daily', 'pixelonwp_daily_license_check');
    }
    $loader->add_action('pixelonwp_daily_license_check', $this, 'verify_license_status');
  }

  /**
   * Check if the current installation has an active, valid license.
   *
   * @since 1.0.0
   * @return bool True if active, false otherwise.
   */
  public static function is_license_active(): bool
  {
    $license_data = get_option(self::LICENSE_OPTION_KEY, []);

    if (empty($license_data) || !is_array($license_data)) {
      return false;
    }

    return isset($license_data['status']) && 'active' === $license_data['status'];
  }

  /**
   * Get stored license information.
   *
   * @since 1.0.0
   * @return array License data array.
   */
  public static function get_license_data(): array
  {
    $defaults = [
      'status' => 'inactive',
      'key' => '',
      'expires' => '',
      'activations' => 0,
    ];
    $data = get_option(self::LICENSE_OPTION_KEY, []);
    return wp_parse_args($data, $defaults);
  }

  /**
   * AJAX handler for activating a license key.
   *
   * @since 1.0.0
   * @return void
   */
  public function ajax_activate_license(): void
  {
    check_ajax_referer('pixelonwp_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';

    if (empty($license_key)) {
      wp_send_json_error(['message' => __('Please enter a valid license key.', 'pixel-on-wp')]);
    }

    $response = $this->call_remote_api('activate', $license_key);

    if (is_wp_error($response)) {
      wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!is_array($data) || empty($data['success'])) {
      $error_msg = isset($data['message']) ? $data['message'] : __('License activation failed.', 'pixel-on-wp');
      wp_send_json_error(['message' => $error_msg]);
    }

    $license_info = [
      'status' => 'active',
      'key' => $license_key,
      'expires' => isset($data['expires']) ? sanitize_text_field($data['expires']) : '',
      'activations' => isset($data['activations']) ? absint($data['activations']) : 1,
    ];

    update_option(self::LICENSE_OPTION_KEY, $license_info);

    wp_send_json_success(['message' => __('License activated successfully!', 'pixel-on-wp')]);
  }

  /**
   * AJAX handler for deactivating a license key.
   *
   * @since 1.0.0
   * @return void
   */
  public function ajax_deactivate_license(): void
  {
    check_ajax_referer('pixelonwp_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $license_data = self::get_license_data();
    if (empty($license_data['key'])) {
      wp_send_json_error(['message' => __('No active license found to deactivate.', 'pixel-on-wp')]);
    }

    $this->call_remote_api('deactivate', $license_data['key']);

    delete_option(self::LICENSE_OPTION_KEY);

    wp_send_json_success(['message' => __('License deactivated successfully.', 'pixel-on-wp')]);
  }

  /**
   * Scheduled background check to verify license validity.
   *
   * @since 1.0.0
   * @return void
   */
  public function verify_license_status(): void
  {
    $license_data = self::get_license_data();
    if (empty($license_data['key']) || 'active' !== $license_data['status']) {
      return;
    }

    $response = $this->call_remote_api('check', $license_data['key']);
    if (is_wp_error($response)) {
      return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (is_array($data) && isset($data['status'])) {
      $license_data['status'] = sanitize_text_field($data['status']);
      update_option(self::LICENSE_OPTION_KEY, $license_data);
    }
  }

  /**
   * Helper to communicate with the remote licensing server.
   *
   * @since 1.0.0
   * @param string $action      Action ('activate', 'deactivate', 'check').
   * @param string $license_key License key string.
   * @return array|\WP_Error    HTTP response or WP_Error.
   */
  private function call_remote_api(string $action, string $license_key)
  {
    $payload = [
      'action' => $action,
      'license_key' => $license_key,
      'domain' => wp_parse_url(home_url(), PHP_URL_HOST),
      'plugin' => 'pixel-on-wp',
    ];

    $args = [
      'body' => wp_json_encode($payload),
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'timeout' => 15,
    ];

    return wp_remote_post(self::API_ENDPOINT, $args);
  }
}