<?php
/**
 * Event Model Class.
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
 * PixelOnWP_Event_Model Class.
 *
 * Represents an individual tracking event structure, handling data sanitization, schema validation,
 * and database persistence formatting.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Event_Model
{

  /**
   * Event Name.
   *
   * @var string
   */
  private $event_name;

  /**
   * Unique Event ID for deduplication.
   *
   * @var string
   */
  private $event_id;

  /**
   * Event Timestamp.
   *
   * @var int
   */
  private $event_time;

  /**
   * Event Source URL.
   *
   * @var string
   */
  private $event_source_url;

  /**
   * Action Source (e.g. website, email, phone_call).
   *
   * @var string
   */
  private $action_source;

  /**
   * User Data parameters (hashed PII, IP, UA, fbp, fbc).
   *
   * @var array
   */
  private $user_data;

  /**
   * Custom Data parameters (product info, currency, value).
   *
   * @var array
   */
  private $custom_data;

  /**
   * Constructor.
   *
   * @since 1.0.0
   * @param string $event_name Event name.
   * @param array  $custom_data Custom data parameters.
   * @param string $event_id   Optional event ID.
   */
  public function __construct(string $event_name, array $custom_data = [], string $event_id = '')
  {
    $this->event_name = sanitize_text_field($event_name);
    $this->event_id = !empty($event_id) ? sanitize_text_field($event_id) : \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::generate_event_id();
    $this->event_time = time();
    $this->event_source_url = esc_url_raw(isset($_SERVER['HTTP_REFERER']) ? wp_unslash($_SERVER['HTTP_REFERER']) : home_url());
    $this->action_source = 'website';
    $this->custom_data = $custom_data;
    $this->user_data = $this->build_user_data();
  }

  /**
   * Build and sanitize standard user data payload including IP, User Agent, cookies, and advanced matching.
   *
   * @since 1.0.0
   * @return array Sanitized user data array.
   */
  private function build_user_data(): array
  {
    $client_ip = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_ip();
    $user_agent = \PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_client_user_agent();

    $fbp = isset($_COOKIE['_fbp']) ? sanitize_text_field(wp_unslash($_COOKIE['_fbp'])) : '';
    $fbc = isset($_COOKIE['_fbc']) ? sanitize_text_field(wp_unslash($_COOKIE['_fbc'])) : '';

    $user_data = [
      'client_ip_address' => $client_ip,
      'client_user_agent' => $user_agent,
    ];

    if (!empty($fbp)) {
      $user_data['fbp'] = $fbp;
    }
    if (!empty($fbc)) {
      $user_data['fbc'] = $fbc;
    }

    $settings = \PixelOnWP\Includes\Admin\PixelOnWP_Settings::get_settings();

    // Advanced Matching for logged-in users
    if (is_user_logged_in() && isset($settings['advanced_matching']) && '1' === $settings['advanced_matching']) {
      $current_user = wp_get_current_user();
      if (!empty($current_user->user_email)) {
        $user_data['em'] = [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::hash_user_data($current_user->user_email, 'email')];
      }
      if (!empty($current_user->user_firstname)) {
        $user_data['fn'] = [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::hash_user_data($current_user->user_firstname, 'name')];
      }
      if (!empty($current_user->user_lastname)) {
        $user_data['ln'] = [\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::hash_user_data($current_user->user_lastname, 'name')];
      }
    }

    return $user_data;
  }

  /**
   * Export complete event model data as an associative array formatted for Meta Conversions API.
   *
   * @since 1.0.0
   * @return array Formatted event payload.
   */
  public function to_array(): array
  {
    return [
      'event_name' => $this->event_name,
      'event_time' => $this->event_time,
      'event_id' => $this->event_id,
      'event_source_url' => $this->event_source_url,
      'action_source' => $this->action_source,
      'user_data' => $this->user_data,
      'custom_data' => $this->custom_data,
    ];
  }

  /**
   * Get event name.
   *
   * @since 1.0.0
   * @return string Event name.
   */
  public function get_event_name(): string
  {
    return $this->event_name;
  }

  /**
   * Get unique event ID.
   *
   * @since 1.0.0
   * @return string Event ID.
   */
  public function get_event_id(): string
  {
    return $this->event_id;
  }
}