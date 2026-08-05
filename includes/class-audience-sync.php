<?php
/**
 * Advanced Meta Custom Audience Syncing Class.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP\Includes;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Audience_Sync Class.
 *
 * Automatically segments WooCommerce buyers, tracks cart abandonment, hashes data,
 * and syncs custom audiences with the Meta Graph API.
 */
class PixelOnWP_Audience_Sync {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('woocommerce_checkout_process', $this, 'capture_checkout_draft');
    $loader->add_action('woocommerce_thankyou', $this, 'handle_purchase_audience_sync');
    $loader->add_action('roas_hourly_audience_eval', $this, 'sync_abandoned_carts');
    $loader->add_action('wp_ajax_roas_sync_vip_audience', $this, 'ajax_sync_vip_audience');
  }

  /**
   * AJAX handler to trigger manual synchronization of VIP CUSTOM audience list.
   *
   * @return void
   */
  public function ajax_sync_vip_audience(): void {
    check_ajax_referer('pixelonwp_roas_nonce', 'nonce');

    if (!current_user_can(\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability())) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $vip_synced = self::sync_vip_segment();

    if (is_wp_error($vip_synced)) {
      wp_send_json_error(['message' => $vip_synced->get_error_message()]);
    } else {
      wp_send_json_success(['message' => sprintf(esc_html__('Custom Audience VIP list manually synced. Received %1$d users.', 'pixel-on-wp'), $vip_synced)]);
    }
  }

  /**
   * Capture user details on checkout submission (fails/pending) for abandonment tracking.
   *
   * @return void
   */
  public function capture_checkout_draft(): void {
    $email = isset($_POST['billing_email']) ? sanitize_email(wp_unslash($_POST['billing_email'])) : '';
    if (empty($email)) {
      return;
    }

    $drafts = get_option('pixelonwp_checkout_drafts', []);
    $drafts[$email] = [
      'email'      => $email,
      'phone'      => isset($_POST['billing_phone']) ? sanitize_text_field(wp_unslash($_POST['billing_phone'])) : '',
      'first_name' => isset($_POST['billing_first_name']) ? sanitize_text_field(wp_unslash($_POST['billing_first_name'])) : '',
      'last_name'  => isset($_POST['billing_last_name']) ? sanitize_text_field(wp_unslash($_POST['billing_last_name'])) : '',
      'timestamp'  => time(),
      'status'     => 'pending',
    ];

    update_option('pixelonwp_checkout_drafts', $drafts);
  }

  /**
   * Hook into WooCommerce checkout success. Mark drafts completed and sync to Recent Purchasers.
   *
   * @param int $order_id WC Order ID.
   * @return void
   */
  public function handle_purchase_audience_sync(int $order_id): void {
    $order = wc_get_order($order_id);
    if (!$order) {
      return;
    }

    $email = strtolower(trim($order->get_billing_email()));
    if (empty($email)) {
      return;
    }

    // Mark draft as completed
    $drafts = get_option('pixelonwp_checkout_drafts', []);
    if (isset($drafts[$email])) {
      $drafts[$email]['status'] = 'completed';
      update_option('pixelonwp_checkout_drafts', $drafts);
    }

    // Push to Recent Purchasers Custom Audience if enabled
    $exclusion_audience_id = trim(get_option('pixelonwp_meta_purchasers_audience_id', ''));
    if (!empty($exclusion_audience_id)) {
      $phone = strtolower(trim($order->get_billing_phone()));
      $first = strtolower(trim($order->get_billing_first_name()));
      $last  = strtolower(trim($order->get_billing_last_name()));

      $hashed_user = [
        self::hash_user_data($email),
        self::hash_user_data($phone),
        self::hash_user_data($first),
        self::hash_user_data($last),
      ];

      PixelOnWP_Meta_Api_Service::sync_custom_audience($exclusion_audience_id, [$hashed_user]);
    }
  }

  /**
   * Hourly Cron Hook. Pushes abandoned carts (>2 hours pending) to Meta Custom Audience.
   *
   * @return void
   */
  public function sync_abandoned_carts(): void {
    $abandon_audience_id = trim(get_option('pixelonwp_meta_abandon_audience_id', ''));
    if (empty($abandon_audience_id)) {
      return;
    }

    $drafts = get_option('pixelonwp_checkout_drafts', []);
    $abandoned_users = [];
    $two_hours_ago = time() - (2 * HOUR_IN_SECONDS);
    $one_day_ago   = time() - (24 * HOUR_IN_SECONDS);

    foreach ($drafts as $email => $data) {
      if ($data['status'] === 'pending' && $data['timestamp'] <= $two_hours_ago && $data['timestamp'] >= $one_day_ago) {
        $abandoned_users[] = [
          self::hash_user_data($data['email']),
          self::hash_user_data($data['phone']),
          self::hash_user_data($data['first_name']),
          self::hash_user_data($data['last_name']),
        ];
        
        // Remove from list after processing to avoid duplicate syncs
        unset($drafts[$email]);
      } elseif ($data['timestamp'] < $one_day_ago) {
        // Clean up older records
        unset($drafts[$email]);
      }
    }

    update_option('pixelonwp_checkout_drafts', $drafts);

    if (!empty($abandoned_users)) {
      PixelOnWP_Meta_Api_Service::sync_custom_audience($abandon_audience_id, $abandoned_users);
    }
  }

  /**
   * Sync VIP customers based on lifetime spend.
   *
   * @return int|\WP_Error Number of synced users or WP_Error.
   */
  public static function sync_vip_segment(): int|\WP_Error {
    $vip_audience_id = trim(get_option('pixelonwp_meta_vip_audience_id', ''));
    if (empty($vip_audience_id)) {
      return new \WP_Error('missing_audience_id', __('VIP Audience ID is not configured.', 'pixel-on-wp'));
    }

    $vip_threshold = (float)get_option('pixelonwp_vip_spend_threshold', 10000.0);

    // Fetch all completed/processing WooCommerce orders
    $orders = wc_get_orders([
      'limit'  => -1,
      'status' => ['completed', 'processing'],
    ]);

    $customer_spend = [];
    foreach ($orders as $order) {
      $email = strtolower(trim($order->get_billing_email()));
      if (empty($email)) continue;

      if (!isset($customer_spend[$email])) {
        $customer_spend[$email] = [
          'email' => $email,
          'phone' => strtolower(trim($order->get_billing_phone())),
          'first' => strtolower(trim($order->get_billing_first_name())),
          'last'  => strtolower(trim($order->get_billing_last_name())),
          'total' => 0.0,
        ];
      }
      $customer_spend[$email]['total'] += (float)$order->get_total();
    }

    $vip_users = [];
    foreach ($customer_spend as $cust) {
      if ($cust['total'] >= $vip_threshold) {
        $vip_users[] = [
          self::hash_user_data($cust['email']),
          self::hash_user_data($cust['phone']),
          self::hash_user_data($cust['first']),
          self::hash_user_data($cust['last']),
        ];
      }
    }

    if (empty($vip_users)) {
      return 0;
    }

    return PixelOnWP_Meta_Api_Service::sync_custom_audience($vip_audience_id, $vip_users);
  }

  /**
   * Helper to normalize and SHA-256 hash input values.
   *
   * @param string $data Value to hash.
   * @return string Hashed value.
   */
  public static function hash_user_data(string $data): string {
    $clean = trim(strtolower($data));
    return hash('sha256', $clean);
  }
}
