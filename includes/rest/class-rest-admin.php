<?php
/**
 * Admin REST API Controller Class.
 *
 * Handles saving and loading settings from the SPA.
 *
 * @package PixelOnWP\Includes\Rest
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Rest;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

class PixelOnWP_Rest_Admin
{
  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void
  {
    $loader->add_action('rest_api_init', $this, 'register_rest_routes');
  }

  public function register_rest_routes(): void
  {
    register_rest_route('pixelonwp/v1', '/settings', [
      [
        'methods' => \WP_REST_Server::READABLE,
        'callback' => [$this, 'get_settings'],
        'permission_callback' => [$this, 'check_permission'],
      ],
      [
        'methods' => \WP_REST_Server::CREATABLE,
        'callback' => [$this, 'save_settings'],
        'permission_callback' => [$this, 'check_permission'],
      ]
    ]);
  }

  public function check_permission()
  {
    return current_user_can('manage_options');
  }

  public function get_settings()
  {
    $settings = get_option('pixelonwp_settings', []);
    return new \WP_REST_Response(['success' => true, 'settings' => $settings], 200);
  }

  public function save_settings(\WP_REST_Request $request)
  {
    $params = $request->get_json_params();
    if (!is_array($params)) {
      return new \WP_REST_Response(['success' => false, 'message' => 'Invalid data'], 400);
    }

    $current_settings = get_option('pixelonwp_settings', []);
    
    // Merge and sanitize
    foreach ($params as $key => $value) {
      $current_settings[sanitize_key($key)] = sanitize_text_field($value);
    }

    update_option('pixelonwp_settings', $current_settings);

    return new \WP_REST_Response(['success' => true, 'message' => 'Settings saved successfully.', 'settings' => $current_settings], 200);
  }
}
