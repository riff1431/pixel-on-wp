<?php
/**
 * Log Manager Class.
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
 * PixelOnWP_Log_Manager Class.
 *
 * Handles retrieval, management, filtering, and clearing of event and fraud logs for the diagnostics dashboard.
 *
 * @package PixelOnWP\Includes\Diagnostics
 * @since 1.0.0
 */
class PixelOnWP_Log_Manager
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
    $loader->add_action('wp_ajax_pixelonwp_clear_logs', $this, 'ajax_clear_logs');
    $loader->add_action('wp_ajax_pixelonwp_retry_event', $this, 'ajax_retry_event');
  }

  /**
   * Get paginated event logs with optional status filtering.
   *
   * @since 1.0.0
   * @param int    $per_page Number of items per page.
   * @param int    $page     Current page number.
   * @param string $status   Optional status filter ('success', 'failed', 'pending').
   * @return array           Array of log items and pagination metadata.
   */
  public static function get_paginated_event_logs(int $per_page = 20, int $page = 1, string $status = ''): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';
    $offset = (max(1, $page) - 1) * $per_page;

    if (!empty($status)) {
      $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d",
        $status,
        $per_page,
        $offset
      );
      $count_query = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE status = %s", $status);
    } else {
      $query = $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
      );
      $count_query = "SELECT COUNT(*) FROM {$table}";
    }

    $items = $wpdb->get_results($query, ARRAY_A) ?: [];
    $total_items = (int) $wpdb->get_var($count_query);
    $total_pages = ceil($total_items / $per_page);

    return [
      'items' => $items,
      'total_items' => $total_items,
      'total_pages' => $total_pages,
      'current_page' => $page,
    ];
  }

  /**
   * Get paginated fraud logs.
   *
   * @since 1.0.0
   * @param int $per_page Number of items per page.
   * @param int $page     Current page number.
   * @return array        Array of fraud log items and pagination metadata.
   */
  public static function get_paginated_fraud_logs(int $per_page = 20, int $page = 1): array
  {
    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_fraud_logs';
    $offset = (max(1, $page) - 1) * $per_page;

    $query = $wpdb->prepare(
      "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
      $per_page,
      $offset
    );

    $items = $wpdb->get_results($query, ARRAY_A) ?: [];
    $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $total_pages = ceil($total_items / $per_page);

    return [
      'items' => $items,
      'total_items' => $total_items,
      'total_pages' => $total_pages,
      'current_page' => $page,
    ];
  }

  /**
   * AJAX handler to clear log tables.
   *
   * @since 1.0.0
   * @return void
   */
  public function ajax_clear_logs(): void
  {
    check_ajax_referer('pixelonwp_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $log_type = isset($_POST['log_type']) ? sanitize_text_field(wp_unslash($_POST['log_type'])) : 'events';

    global $wpdb;

    if ('events' === $log_type || 'all' === $log_type) {
      $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}pixelonwp_event_logs");
    }

    if ('fraud' === $log_type || 'all' === $log_type) {
      $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}pixelonwp_fraud_logs");
    }

    wp_send_json_success(['message' => __('Logs cleared successfully.', 'pixel-on-wp')]);
  }

  /**
   * AJAX handler to retry a failed event log.
   *
   * @since 1.0.0
   * @return void
   */
  public function ajax_retry_event(): void
  {
    check_ajax_referer('pixelonwp_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $log_id = isset($_POST['log_id']) ? absint($_POST['log_id']) : 0;
    if (!$log_id) {
      wp_send_json_error(['message' => __('Invalid log ID.', 'pixel-on-wp')]);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'pixelonwp_event_logs';
    $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $log_id), ARRAY_A);

    if (!$log) {
      wp_send_json_error(['message' => __('Log entry not found.', 'pixel-on-wp')]);
    }

    $payload = json_decode($log['payload'], true);
    if (!is_array($payload)) {
      wp_send_json_error(['message' => __('Invalid event payload structure.', 'pixel-on-wp')]);
    }

    // Dispatch via API Client
    $api_result = \PixelOnWP\Includes\Tracking\PixelOnWP_API_Client::send_event($payload);

    $new_status = $api_result['success'] ? 'success' : 'failed';
    $retry_count = (int) $log['retry_count'] + 1;

    $wpdb->update(
      $table,
      [
        'status' => $new_status,
        'retry_count' => $retry_count,
      ],
      ['id' => $log_id],
      ['%s', '%d'],
      ['%d']
    );

    if ($api_result['success']) {
      wp_send_json_success(['message' => __('Event retried successfully and sent to CAPI.', 'pixel-on-wp')]);
    } else {
      wp_send_json_error(['message' => __('Event retry failed: ', 'pixel-on-wp') . (isset($api_result['error']) ? $api_result['error'] : 'Unknown error')]);
    }
  }
}