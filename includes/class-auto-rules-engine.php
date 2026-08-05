<?php
/**
 * Auto-Rules & Automation Engine Class.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP\Includes;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Auto_Rules_Engine Class.
 *
 * Checks campaign performance rules via WP-Cron, scales budgets, pauses low-ROAS,
 * and pauses ads for out-of-stock items.
 */
class PixelOnWP_Auto_Rules_Engine {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('roas_evaluate_automation_rules', $this, 'evaluate_rules');
    $loader->add_action('woocommerce_product_set_stock_status', $this, 'handle_out_of_stock_ad_pauser', 10, 3);
    $loader->add_action('woocommerce_variation_set_stock_status', $this, 'handle_out_of_stock_variation_ad_pauser', 10, 3);
  }

  /**
   * Run evaluations for budget scale and budget cut rules over a 3-day window.
   *
   * @return void
   */
  public function evaluate_rules(): void {
    $autoscale_enabled = get_option('pixelonwp_enable_autoscale', '0') === '1';
    $budgetcut_enabled = get_option('pixelonwp_enable_budgetcut', '0') === '1';

    if (!$autoscale_enabled && !$budgetcut_enabled) {
      return;
    }

    $scale_roas = (float)get_option('pixelonwp_autoscale_threshold', 3.0);
    $cut_roas   = (float)get_option('pixelonwp_budgetcut_threshold', 1.0);

    // Evaluate over the last 3 days
    $since = date('Y-m-d', strtotime('-2 days'));
    $until = date('Y-m-d');

    $insights = PixelOnWP_Meta_Api_Service::fetch_campaign_insights($since, $until);
    if (is_wp_error($insights) || !is_array($insights)) {
      return;
    }

    $order_args = [
      'limit'        => -1,
      'status'       => ['completed', 'processing', 'on-hold'],
      'date_created' => $since . '...' . $until,
      'meta_key'     => '_tracked_ad_campaign_id',
      'meta_compare' => 'EXISTS',
    ];
    $orders = wc_get_orders($order_args);

    $campaign_revenue = [];
    foreach ($orders as $order) {
      $c_id = $order->get_meta('_tracked_ad_campaign_id');
      if (empty($c_id)) continue;
      
      if (!isset($campaign_revenue[$c_id])) {
        $campaign_revenue[$c_id] = 0.0;
      }
      $campaign_revenue[$c_id] += (float)$order->get_total();
    }

    foreach ($insights as $campaign) {
      $c_id    = $campaign['campaign_id'] ?? '';
      $c_name  = $campaign['campaign_name'] ?? $c_id;
      $spend   = (float)($campaign['spend'] ?? 0.0);
      $currency = $campaign['account_currency'] ?? 'USD';

      if (empty($c_id) || $spend <= 0.0) {
        continue;
      }

      $normalized_spend = PixelOnWP_Meta_Api_Service::normalize_currency($spend, $currency);
      $revenue = $campaign_revenue[$c_id] ?? 0.0;
      $roas = $revenue / $normalized_spend;

      if ($autoscale_enabled && $roas >= $scale_roas) {
        // High-ROAS: Increase budget by 20%
        $res = PixelOnWP_Meta_Api_Service::scale_campaign_budget($c_id, 1.20);
        if (!is_wp_error($res) && $res) {
          $this->log_automation_action($c_id, 'SCALE_UP', sprintf(__('ROAS was %1$sx. Scaled daily budget up by 20%%.', 'pixel-on-wp'), number_format($roas, 2)));
        }
      } elseif ($budgetcut_enabled && $roas < $cut_roas) {
        // Low-ROAS: Reduce budget by 50%
        $res = PixelOnWP_Meta_Api_Service::scale_campaign_budget($c_id, 0.50);
        if (!is_wp_error($res) && $res) {
          $this->log_automation_action($c_id, 'SCALE_DOWN', sprintf(__('ROAS was %1$sx. Scaled daily budget down by 50%%.', 'pixel-on-wp'), number_format($roas, 2)));
        } else {
          // If scaling down fails or returns error, pause campaign
          PixelOnWP_Meta_Api_Service::pause_meta_object($c_id);
          $this->log_automation_action($c_id, 'PAUSE', sprintf(__('ROAS was %1$sx. Paused campaign due to low return.', 'pixel-on-wp'), number_format($roas, 2)));
        }
      }
    }
  }

  /**
   * Hook for product stock change to pause ads for out-of-stock items.
   *
   * @param int $product_id Product ID.
   * @param string $status Stock status.
   * @param \WC_Product $product Product instance.
   * @return void
   */
  public function handle_out_of_stock_ad_pauser(int $product_id, string $status, \WC_Product $product): void {
    if ('outofstock' !== $status) {
      return;
    }
    $this->pause_ads_for_product($product);
  }

  /**
   * Hook for variation stock change to pause ads for out-of-stock variations.
   *
   * @param int $variation_id Variation ID.
   * @param string $status Stock status.
   * @param \WC_Product_Variation $variation Variation instance.
   * @return void
   */
  public function handle_out_of_stock_variation_ad_pauser(int $variation_id, string $status, \WC_Product_Variation $variation): void {
    if ('outofstock' !== $status) {
      return;
    }
    $this->pause_ads_for_product($variation);
  }

  /**
   * Pause campaign associated with out-of-stock items.
   *
   * @param \WC_Product $product Product or Variation instance.
   * @return void
   */
  private function pause_ads_for_product(\WC_Product $product): void {
    $pauser_enabled = get_option('pixelonwp_enable_stock_pauser', '0') === '1';
    if (!$pauser_enabled) {
      return;
    }

    $sku = $product->get_sku();
    $prod_id = $product->get_id();

    // Query WooCommerce orders to find associated campaign ID
    $orders = wc_get_orders([
      'limit'        => 30,
      'status'       => ['completed', 'processing', 'on-hold'],
      'meta_key'     => '_tracked_ad_campaign_id',
      'meta_compare' => 'EXISTS',
    ]);

    $campaign_ids = [];
    foreach ($orders as $order) {
      foreach ($order->get_items() as $item) {
        if ($item->get_product_id() === $prod_id || $item->get_variation_id() === $prod_id || (!empty($sku) && $item->get_product() && $item->get_product()->get_sku() === $sku)) {
          $c_id = $order->get_meta('_tracked_ad_campaign_id');
          if (!empty($c_id)) {
            $campaign_ids[] = $c_id;
          }
        }
      }
    }

    $campaign_ids = array_unique($campaign_ids);
    foreach ($campaign_ids as $c_id) {
      $res = PixelOnWP_Meta_Api_Service::pause_meta_object($c_id);
      if (!is_wp_error($res) && $res) {
        $this->log_automation_action($c_id, 'STOCK_PAUSE', sprintf(__('Product SKU %1$s is out of stock. Automatically paused ad campaign.', 'pixel-on-wp'), $sku ?: $prod_id));
      }
    }
  }

  /**
   * Log automation actions in database options.
   *
   * @param string $target Meta Campaign/AdSet ID or description.
   * @param string $action_type Action type keyword.
   * @param string $message Detailed description log.
   * @return void
   */
  public function log_automation_action(string $target, string $action_type, string $message): void {
    $logs = get_option('pixelonwp_automation_logs', []);
    
    // Find name
    $name = $target;
    
    $logs[] = [
      'timestamp'   => time(),
      'campaign_id' => $target,
      'name'        => $name,
      'action'      => $action_type,
      'message'     => $message,
    ];

    if (count($logs) > 100) {
      $logs = array_slice($logs, -100);
    }
    update_option('pixelonwp_automation_logs', $logs);
  }
}
