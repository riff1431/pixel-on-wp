<?php
/**
 * Background Cron Sync and Data Caching Class.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP\Includes;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Cron_Sync Class.
 *
 * Schedules and executes daily background tasks to query Meta Graph API insights,
 * cache metrics locally in a date-campaign option matrix, and trigger automation.
 */
class PixelOnWP_Cron_Sync {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('init', $this, 'schedule_cron_jobs');
    $loader->add_action('roas_daily_meta_sync', $this, 'cache_meta_insights');
    $loader->add_action('wp_ajax_roas_force_cache_sync', $this, 'ajax_force_cache_sync');
  }

  /**
   * Schedule the daily and hourly cron jobs if not already registered.
   *
   * @return void
   */
  public function schedule_cron_jobs(): void {
    if (!wp_next_scheduled('roas_daily_meta_sync')) {
      wp_schedule_event(time(), 'daily', 'roas_daily_meta_sync');
    }

    if (!wp_next_scheduled('roas_evaluate_automation_rules')) {
      wp_schedule_event(time(), 'hourly', 'roas_evaluate_automation_rules');
    }

    if (!wp_next_scheduled('roas_hourly_audience_eval')) {
      wp_schedule_event(time(), 'hourly', 'roas_hourly_audience_eval');
    }
  }

  /**
   * AJAX handler to force clear transients and trigger background insights sync.
   *
   * @return void
   */
  public function ajax_force_cache_sync(): void {
    check_ajax_referer('pixelonwp_roas_nonce', 'nonce');

    if (!current_user_can(\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability())) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    // Force clear insights transient cache
    $ad_account_id = trim(get_option('pixelonwp_meta_ad_account_id', ''));
    if (!empty($ad_account_id)) {
      if (strpos($ad_account_id, 'act_') !== 0) {
        $ad_account_id = 'act_' . $ad_account_id;
      }
      $since = date('Y-m-d', strtotime('-59 days'));
      $until = date('Y-m-d');
      delete_transient('pixelonwp_roas_' . md5($ad_account_id . '_' . $since . '_' . $until . '_1'));
    }

    $this->cache_meta_insights();
    wp_send_json_success(['message' => __('Insights cache successfully synchronized.', 'pixel-on-wp')]);
  }

  /**
   * Query Meta API for the past 60 days of daily breakdown data and cache it locally in a WP Option.
   *
   * @return void
   */
  public function cache_meta_insights(): void {
    $since = date('Y-m-d', strtotime('-59 days'));
    $until = date('Y-m-d');

    // Fetch breakdown daily insights from Meta API (time_increment = 1)
    $insights = PixelOnWP_Meta_Api_Service::fetch_campaign_insights($since, $until, 1);

    if (is_wp_error($insights) || !is_array($insights)) {
      return;
    }

    $cached = get_option('pixelonwp_cached_meta_insights', []);

    foreach ($insights as $row) {
      $date = $row['date_start'] ?? '';
      $c_id = $row['campaign_id'] ?? '';
      if (empty($date) || empty($c_id)) continue;

      if (!isset($cached[$date])) {
        $cached[$date] = [];
      }

      $cached[$date][$c_id] = [
        'campaign_id'        => $c_id,
        'campaign_name'      => $row['campaign_name'] ?? $c_id,
        'spend'              => (float)($row['spend'] ?? 0.0),
        'impressions'        => (int)($row['impressions'] ?? 0),
        'inline_link_clicks' => (int)($row['inline_link_clicks'] ?? 0),
        'account_currency'   => $row['account_currency'] ?? 'USD',
      ];
    }

    // Keep only the last 60 days to prevent option bloat
    krsort($cached);
    $cached = array_slice($cached, 0, 60, true);

    update_option('pixelonwp_cached_meta_insights', $cached);
  }

  /**
   * Trigger a manual forced synchronization.
   *
   * @return bool True if successful.
   */
  public function perform_manual_sync(): bool {
    $this->cache_meta_insights();
    return true;
  }
}
