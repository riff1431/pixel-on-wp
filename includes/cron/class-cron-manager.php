<?php
/**
 * Cron Manager Class.
 *
 * @package PixelOnWP\Includes\Cron
 * @since 1.0.0
 */

namespace PixelOnWP\Includes\Cron;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Cron_Manager Class.
 *
 * Manages scheduled background cron tasks for batch event queue processing, log purging, and license checks.
 *
 * @package PixelOnWP\Includes\Cron
 * @since 1.0.0
 */
class PixelOnWP_Cron_Manager
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
    $loader->add_action('init', $this, 'schedule_events');
    $loader->add_filter('cron_schedules', $this, 'add_cron_intervals');
    $loader->add_action('pixelonwp_purge_old_logs_cron', $this, 'purge_old_logs');
  }

  /**
   * Schedule custom cron intervals and events if not already scheduled.
   *
   * @since 1.0.0
   * @return void
   */
  public function schedule_events(): void
  {
    // Schedule daily log purging if not scheduled
    if (!wp_next_scheduled('pixelonwp_purge_old_logs_cron')) {
      wp_schedule_event(time(), 'daily', 'pixelonwp_purge_old_logs_cron');
    }

  }

  /**
   * Add custom cron intervals.
   *
   * @param array $schedules Existing schedules.
   * @return array
   */
  public function add_cron_intervals($schedules)
  {
    return $schedules;
  }

  /**
   * Purge old event and fraud logs older than 30 days to keep the database optimized.
   *
   * @since 1.0.0
   * @return void
   */
  public function purge_old_logs(): void
  {
    global $wpdb;

    $event_table = $wpdb->prefix . 'pixelonwp_event_logs';
    $fraud_table = $wpdb->prefix . 'pixelonwp_fraud_logs';

    $cutoff_date = gmdate('Y-m-d H:i:s', strtotime('-30 days'));

    // Delete old event logs
    $event_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $event_table));
    if ($event_table_exists) {
      $wpdb->query(
        $wpdb->prepare(
          "DELETE FROM {$event_table} WHERE created_at < %s",
          $cutoff_date
        )
      );
    }

    // Delete old fraud logs
    $fraud_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $fraud_table));
    if ($fraud_table_exists) {
      $wpdb->query(
        $wpdb->prepare(
          "DELETE FROM {$fraud_table} WHERE created_at < %s",
          $cutoff_date
        )
      );
    }
  }
}