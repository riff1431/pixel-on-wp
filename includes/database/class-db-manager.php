<?php
/**
 * Database Manager Class.
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
 * PixelOnWP_DB_Manager Class.
 *
 * Handles database operations, table queries, and data sanitization for tracking logs.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_DB_Manager
{

  /**
   * Get event logs with pagination and filtering.
   *
   * @since 1.0.0
   * @param int    $per_page Number of items per page.
   * @param int    $offset   Offset for pagination.
   * @param string $status   Optional status filter.
   * @return array           Array of event log records.
   */
  public static function get_event_logs(int $per_page = 20, int $offset = 0, string $status = ''): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    if (!empty($status)) {
      $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d",
        $status,
        $per_page,
        $offset
      );
    } else {
      $query = $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
      );
    }

    return $wpdb->get_results($query, ARRAY_A) ?: [];
  }

  /**
   * Get total count of event logs.
   *
   * @since 1.0.0
   * @param string $status Optional status filter.
   * @return int Total number of logs.
   */
  public static function count_event_logs(string $status = ''): int
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    if (!empty($status)) {
      $query = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", $status);
    } else {
      $query = "SELECT COUNT(*) FROM {$table}";
    }

    return (int) $wpdb->get_var($query);
  }

  /**
   * Get fraud logs with pagination.
   *
   * @since 1.0.0
   * @param int $per_page Number of items per page.
   * @param int $offset   Offset for pagination.
   * @return array        Array of fraud log records.
   */
  public static function get_fraud_logs(int $per_page = 20, int $offset = 0): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    $query = $wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
      $per_page,
      $offset
    );

    return $wpdb->get_results($query, ARRAY_A) ?: [];
  }

  /**
   * Get total count of fraud logs.
   *
   * @since 1.0.0
   * @return int Total number of fraud logs.
   */
  public static function count_fraud_logs(): int
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';
    return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
  }

  /**
   * Clean up old logs based on retention days.
   *
   * @since 1.0.0
   * @param int $days Number of days to retain logs.
   * @return void
   */
  public static function prune_logs(int $days = 30): void
  {
    global $wpdb;
    $event_table = $wpdb->prefix . 'pixelonwp_event_logs';
    $fraud_table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    $date_threshold = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

    $wpdb->query($wpdb->prepare("DELETE FROM {$event_table} WHERE created_at < %s", $date_threshold));
    $wpdb->query($wpdb->prepare("DELETE FROM {$fraud_table} WHERE created_at < %s", $date_threshold));
  }
}