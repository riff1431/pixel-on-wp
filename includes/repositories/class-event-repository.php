<?php
/**
 * Event Repository Class.
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
 * PixelOnWP_Event_Repository Class.
 *
 * Encapsulates database queries and data access operations for event logs and analytics.
 *
 * @package PixelOnWP\Includes\Tracking
 * @since 1.0.0
 */
class PixelOnWP_Event_Repository
{

  /**
   * Retrieve paginated event logs from the database.
   *
   * @since 1.0.0
   * @param int    $per_page Number of items per page.
   * @param int    $offset   Query offset.
   * @param string $status   Optional status filter ('success', 'failed', 'pending').
   * @return array           Array of event log records.
   */
  public static function get_events(int $per_page = 20, int $offset = 0, string $status = ''): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return [];
    }

    if (!empty($status)) {
      $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d",
        sanitize_text_field($status),
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

    return $wpdb->get_results($query, ARRAY_A);
  }

  /**
   * Get total count of logged events, optionally filtered by status.
   *
   * @since 1.0.0
   * @param string $status Optional status filter.
   * @return int Total event count.
   */
  public static function get_event_count(string $status = ''): int
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return 0;
    }

    if (!empty($status)) {
      $count = $wpdb->get_var(
        $wpdb->prepare(
          "SELECT COUNT(*) FROM {$table} WHERE status = %s",
          sanitize_text_field($status)
        )
      );
    } else {
      $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    return (int) $count;
  }

  /**
   * Clear all event logs from the database table.
   *
   * @since 1.0.0
   * @return bool True on success, false on failure.
   */
  public static function clear_logs(): bool
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';

    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if (!$table_exists) {
      return false;
    }

    $result = $wpdb->query("TRUNCATE TABLE {$table}");
    return false !== $result;
  }
}