<?php
/**
 * Analytics and Math Engine Class.
 *
 * @package PixelOnWP\Includes
 * @since 1.0.0
 */

namespace PixelOnWP\Includes;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Analytics_Engine Class.
 *
 * Performs calculations for ROAS, CPA, AOV, CTR, CPC, aggregates WooCommerce order data,
 * and handles AJAX dashboard updates strictly using local background cache.
 */
class PixelOnWP_Analytics_Engine {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('wp_ajax_pixelonwp_get_roas_data', $this, 'ajax_get_roas_data');
  }

  /**
   * AJAX handler to query and calculate ROAS analytics dynamically from local cache.
   *
   * @return void
   */
  public function ajax_get_roas_data(): void {
    check_ajax_referer('pixelonwp_roas_nonce', 'nonce');

    if (!current_user_can(\PixelOnWP\Includes\Helpers\PixelOnWP_Helper::get_admin_capability())) {
      wp_send_json_error(['message' => __('Permission denied.', 'pixel-on-wp')]);
    }

    $filter = isset($_POST['date_range']) ? sanitize_text_field(wp_unslash($_POST['date_range'])) : 'last_30_days';
    $start_date = '';
    $end_date   = '';

    switch ($filter) {
      case 'today':
        $start_date = date('Y-m-d');
        $end_date   = date('Y-m-d');
        break;
      case 'last_7_days':
        $start_date = date('Y-m-d', strtotime('-6 days'));
        $end_date   = date('Y-m-d');
        break;
      case 'last_30_days':
      default:
        $start_date = date('Y-m-d', strtotime('-29 days'));
        $end_date   = date('Y-m-d');
        break;
      case 'custom':
        $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : date('Y-m-d', strtotime('-29 days'));
        $end_date = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : date('Y-m-d');
        break;
    }

    // 1. Retrieve Meta Insights STRICTLY from local background option cache
    $cached_data = get_option('pixelonwp_cached_meta_insights', []);
    $insights = [];
    
    $current = strtotime($start_date);
    $last    = strtotime($end_date);
    
    while ($current <= $last) {
      $date_str = date('Y-m-d', $current);
      if (isset($cached_data[$date_str]) && is_array($cached_data[$date_str])) {
        foreach ($cached_data[$date_str] as $c_id => $campaign) {
          if (!isset($insights[$c_id])) {
            $insights[$c_id] = [
              'campaign_id'        => $c_id,
              'campaign_name'      => $campaign['campaign_name'] ?? $c_id,
              'spend'              => 0.0,
              'impressions'        => 0,
              'inline_link_clicks' => 0,
              'account_currency'   => $campaign['account_currency'] ?? 'USD',
            ];
          }
          $insights[$c_id]['spend'] += (float)$campaign['spend'];
          $insights[$c_id]['impressions'] += (int)$campaign['impressions'];
          $insights[$c_id]['inline_link_clicks'] += (int)$campaign['inline_link_clicks'];
        }
      }
      $current = strtotime('+1 day', $current);
    }
    $insights = array_values($insights);

    // 2. Fetch Attributed Orders from WooCommerce
    $order_args = [
      'limit'        => -1,
      'status'       => ['completed', 'processing', 'on-hold'],
      'date_created' => $start_date . '...' . $end_date,
      'meta_key'     => '_tracked_ad_campaign_id',
      'meta_compare' => 'EXISTS',
    ];
    $orders = wc_get_orders($order_args);

    // 3. Perform Calculations and Aggregations
    $summary = [
      'conversions' => 0,
      'revenue'     => 0.0,
      'spend'       => 0.0,
      'roas'        => 0.0,
      'cpa'         => 0.0,
      'aov'         => 0.0,
      'ctr'         => 0.0,
      'cpc'         => 0.0,
      'impressions' => 0,
      'clicks'      => 0,
    ];

    $campaigns = [];
    foreach ($insights as $campaign) {
      $c_id = $campaign['campaign_id'] ?? '';
      $c_name = $campaign['campaign_name'] ?? $c_id;
      $spend = (float)($campaign['spend'] ?? 0.0);
      $currency = $campaign['account_currency'] ?? 'USD';
      $impressions = (int)($campaign['impressions'] ?? 0);
      $clicks = (int)($campaign['inline_link_clicks'] ?? 0);

      $normalized_spend = PixelOnWP_Meta_Api_Service::normalize_currency($spend, $currency);
      
      $summary['spend'] += $normalized_spend;
      $summary['impressions'] += $impressions;
      $summary['clicks'] += $clicks;

      if (!empty($c_id)) {
        $campaigns[$c_id] = [
          'campaign_id' => $c_id,
          'name'        => $c_name,
          'conversions' => 0,
          'revenue'     => 0.0,
          'spend'       => $normalized_spend,
          'cpa'         => 0.0,
          'aov'         => 0.0,
          'roas'        => 0.0,
        ];
      }
    }

    $products = [];
    foreach ($orders as $order) {
      $c_id = $order->get_meta('_tracked_ad_campaign_id');
      $c_name = $order->get_meta('_tracked_ad_campaign_name') ?: $c_id;

      $summary['conversions']++;
      $summary['revenue'] += (float)$order->get_total();

      if (!isset($campaigns[$c_id])) {
        $campaigns[$c_id] = [
          'campaign_id' => $c_id,
          'name'        => $c_name,
          'conversions' => 0,
          'revenue'     => 0.0,
          'spend'       => 0.0,
          'cpa'         => 0.0,
          'aov'         => 0.0,
          'roas'        => 0.0,
        ];
      }
      $campaigns[$c_id]['conversions']++;
      $campaigns[$c_id]['revenue'] += (float)$order->get_total();

      // Product-Level Attribution
      foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product) continue;

        $prod_id = $product->get_id();
        $prod_key = $prod_id . '_' . $c_id;
        $sku = $product->get_sku() ?: __('No SKU', 'pixel-on-wp');

        if (!isset($products[$prod_key])) {
          $products[$prod_key] = [
            'sku'         => $sku,
            'name'        => $product->get_name(),
            'units_sold'  => 0,
            'revenue'     => 0.0,
            'campaign_id' => $c_id,
          ];
        }
        $products[$prod_key]['units_sold'] += (int)$item->get_quantity();
        $products[$prod_key]['revenue'] += (float)$item->get_total();
      }
    }

    // Finalize Summary Metrics
    if ($summary['spend'] > 0) {
      $summary['roas'] = $summary['revenue'] / $summary['spend'];
    }
    if ($summary['conversions'] > 0) {
      $summary['cpa'] = $summary['spend'] / $summary['conversions'];
      $summary['aov'] = $summary['revenue'] / $summary['conversions'];
    }
    if ($summary['impressions'] > 0) {
      $summary['ctr'] = ($summary['clicks'] / $summary['impressions']) * 100;
    }
    if ($summary['clicks'] > 0) {
      $summary['cpc'] = $summary['spend'] / $summary['clicks'];
    }

    // Finalize Campaign Metrics
    foreach ($campaigns as $id => $camp) {
      if ($camp['spend'] > 0) {
        $campaigns[$id]['roas'] = $camp['revenue'] / $camp['spend'];
      }
      if ($camp['conversions'] > 0) {
        $campaigns[$id]['cpa'] = $camp['spend'] / $camp['conversions'];
        $campaigns[$id]['aov'] = $camp['revenue'] / $camp['conversions'];
      }
    }

    wp_send_json_success([
      'summary'   => $summary,
      'campaigns' => array_values($campaigns),
      'products'  => array_values($products),
    ]);
  }
}
