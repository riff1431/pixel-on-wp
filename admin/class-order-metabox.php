<?php
/**
 * Order details metabox for attribution insights.
 *
 * @package PixelOnWP\Admin
 * @since 1.0.0
 */

namespace PixelOnWP\Admin;

if (!defined('ABSPATH')) {
  exit;
}

/**
 * PixelOnWP_Order_Metabox Class.
 *
 * Adds side metabox to order details page showing Meta campaign attribution parameters.
 */
class PixelOnWP_Order_Metabox {

  /**
   * Register hooks.
   *
   * @param \PixelOnWP\PixelOnWP_Loader $loader Loader instance.
   * @return void
   */
  public function register_hooks(\PixelOnWP\PixelOnWP_Loader $loader): void {
    $loader->add_action('add_meta_boxes', $this, 'add_order_attribution_metabox');
  }

  /**
   * Add metabox to WooCommerce Orders.
   * Supports both legacy shop_order post type and new HPOS screen.
   *
   * @return void
   */
  public function add_order_attribution_metabox(): void {
    $screens = ['shop_order', 'woocommerce_page_wc-orders'];

    foreach ($screens as $screen) {
      add_meta_box(
        'pixelonwp_order_attribution',
        __('Meta Ad Attribution & UTMs', 'pixel-on-wp'),
        [$this, 'render_metabox_content'],
        $screen,
        'side',
        'default'
      );
    }
  }

  /**
   * Render metabox content.
   *
   * @param \WP_Post|\WC_Order $post_or_order Post object or WC_Order object depending on screen context.
   * @return void
   */
  public function render_metabox_content($post_or_order): void {
    $order = ($post_or_order instanceof \WP_Post) ? wc_get_order($post_or_order->ID) : $post_or_order;

    if (!$order instanceof \WC_Order) {
      echo '<p>' . esc_html__('No order context found.', 'pixel-on-wp') . '</p>';
      return;
    }

    $campaign_id   = $order->get_meta('_tracked_ad_campaign_id');
    $campaign_name = $order->get_meta('_tracked_ad_campaign_name');
    $fbclid        = $order->get_meta('_tracked_ad_fbclid');
    $timestamp     = $order->get_meta('_tracked_attribution_timestamp');

    if (empty($campaign_id) && empty($fbclid)) {
      echo '<p><i>' . esc_html__('No Meta ad parameters attributed to this order.', 'pixel-on-wp') . '</i></p>';
      return;
    }

    ?>
    <div class="pixelonwp-attribution-metabox">
      <?php if (!empty($campaign_name)) : ?>
        <p><strong><?php esc_html_e('Campaign Name:', 'pixel-on-wp'); ?></strong><br>
        <span style="font-family: monospace;"><?php echo esc_html($campaign_name); ?></span></p>
      <?php endif; ?>

      <?php if (!empty($campaign_id)) : ?>
        <p><strong><?php esc_html_e('Campaign ID:', 'pixel-on-wp'); ?></strong><br>
        <span style="font-family: monospace;"><?php echo esc_html($campaign_id); ?></span></p>
      <?php endif; ?>

      <?php if (!empty($fbclid)) : ?>
        <p><strong><?php esc_html_e('FBCLID:', 'pixel-on-wp'); ?></strong><br>
        <span style="word-break: break-all; font-family: monospace;"><?php echo esc_html($fbclid); ?></span></p>
      <?php endif; ?>

      <?php if (!empty($timestamp)) : ?>
        <p><strong><?php esc_html_e('Attributed On:', 'pixel-on-wp'); ?></strong><br>
        <span><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($timestamp))); ?></span></p>
      <?php endif; ?>

      <hr style="border: 0; border-top: 1px solid #ddd; margin: 12px 0;">
      <p style="font-size: 11px; color: #666; line-height: 1.4;">
        <?php esc_html_e('Note: This order\'s revenue is aggregated in the main Ad Attribution & ROAS Dashboard to calculate true ROI.', 'pixel-on-wp'); ?>
      </p>
    </div>
    <?php
  }
}
