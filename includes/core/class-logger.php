<?php
/**
 * Core Logger Class.
 *
 * @package PixelOnWP\Includes\Core
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Core;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Logger Class.
 *
 * Handles database logging for tracking events, API requests, errors, and diagnostics.
 *
 * @package PixelOnWP\Includes\Core
 * @since 1.0.0
 */
class PixelOnWP_Logger
{

  /**
   * Log an event to the database event logs table.
   *
   * @since 1.0.0
   * @param string $event_name Event name.
   * @param string $event_id   Unique event ID.
   * @param array  $payload    Event payload data.
   * @param string $status     Status ('success', 'failed', 'pending').
   * @return int|false         Insert ID on success, false on failure.
   */
  public function log_event(string $event_name, string $event_id, array $payload, string $status = 'pending', string $platform = 'facebook')
  {
    global $wpdb;

    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    $inserted = $wpdb->insert(
      $table,
      [
        'event_name' => sanitize_text_field($event_name),
        'event_id' => sanitize_text_field($event_id),
        'platform' => sanitize_text_field($platform),
        'payload' => wp_json_encode($payload),
        'status' => sanitize_text_field($status),
        'retry_count' => 0,
        'created_at' => current_time('mysql'),
      ],
      ['%s', '%s', '%s', '%s', '%s', '%d', '%s']
    );

    return $inserted ? $wpdb->insert_id : false;
  }

  /**
   * Log a security or fraud attempt to the database.
   *
   * @since 1.0.0
   * @param string      $ip_address  Visitor IP address.
   * @param string      $reason      Reason for fraud/suspicion log.
   * @param array|null  $request_data Optional request context.
   * @return int|false              Insert ID on success, false on failure.
   */
  public function log_fraud(string $ip_address, string $reason, ?array $request_data = null)
  {
    global $wpdb;

    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    $inserted = $wpdb->insert(
      $table,
      [
        'ip_address' => sanitize_text_field($ip_address),
        'reason' => sanitize_text_field($reason),
        'request_data' => $request_data ? wp_json_encode($request_data) : null,
        'created_at' => current_time('mysql'),
      ],
      ['%s', '%s', '%s', '%s']
    );

    return $inserted ? $wpdb->insert_id : false;
  }
}