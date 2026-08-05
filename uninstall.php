<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up database tables, options, and transients associated with the plugin
 * if uninstallation is confirmed and configured to clear data.
 *
 * @package PixelOnWP
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

/**
 * Cleanup routine for PixelOnWP.
 *
 * Drops custom database tables, removes plugin options, and clears scheduled cron jobs.
 *
 * @since 1.0.0
 * @return void
 */
function PixelOnWP_uninstall(): void
{
  global $wpdb;

  // Check user permissions before executing uninstall procedures.
  if (!current_user_can('activate_plugins')) {
    return;
  }

  // Drop custom database tables if they exist.
  $tables = [
    $wpdb->prefix . 'pixelonwp_event_logs',
    $wpdb->prefix . 'pixelonwp_fraud_logs',
    $wpdb->prefix . 'pixelonwp_queue',
  ];

  foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
  }

  // Delete plugin options and settings.
  $options_to_delete = [
    'pixelonwp_settings',
    'pixelonwp_license_key',
    'pixelonwp_license_status',
    'pixelonwp_version',
  ];

  foreach ($options_to_delete as $option) {
    delete_option($option);
    delete_site_option($option);
  }

  // Clear any active scheduled cron jobs.
  $timestamp = wp_next_scheduled('pixelonwp_background_queue_cron');
  if ($timestamp) {
    wp_unschedule_event($timestamp, 'pixelonwp_background_queue_cron');
  }

  // Clear all plugin transients.
  $wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pixelonwp_%' OR option_name LIKE '_transient_timeout_pixelonwp_%'"
  ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

PixelOnWP_uninstall();